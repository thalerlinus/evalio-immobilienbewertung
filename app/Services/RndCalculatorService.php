<?php

namespace App\Services;

use App\Models\Calculation;
use App\Models\PropertyType;
use App\Models\RenovationCategory;
use App\Models\RenovationExtentWeight;
use App\Models\ScoreFormulaSet;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RndCalculatorService
{
    private const DEFAULT_INTERVAL_STEP = 5;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function calculate(array $payload, ?User $user = null): Calculation
    {
        return DB::transaction(function () use ($payload, $user) {
            $propertyType = PropertyType::where('key', $payload['property_type_key'] ?? null)->first();

            if (! $propertyType) {
                throw ValidationException::withMessages([
                    'property_type_key' => __('Die gewählte Immobilienart existiert nicht.'),
                ]);
            }

            $gnd = (int) ($payload['gnd_override'] ?? $propertyType->gnd ?? 0);
            if ($gnd <= 0) {
                throw ValidationException::withMessages([
                    'gnd_override' => __('Für die ausgewählte Immobilienart muss eine Gesamtnutzungsdauer hinterlegt sein.'),
                ]);
            }

            $baujahr = (int) ($payload['baujahr'] ?? 0);
            $anschaffungsjahr = (int) ($payload['anschaffungsjahr'] ?? 0);
            $steuerjahr = (int) ($payload['steuerjahr'] ?? 0);

            if ($baujahr <= 0) {
                throw ValidationException::withMessages([
                    'baujahr' => __('Das Baujahr ist erforderlich.'),
                ]);
            }

            if ($anschaffungsjahr <= 0) {
                throw ValidationException::withMessages([
                    'anschaffungsjahr' => __('Das Anschaffungsjahr ist erforderlich.'),
                ]);
            }

            if ($steuerjahr <= 0) {
                throw ValidationException::withMessages([
                    'steuerjahr' => __('Das Steuerjahr ist erforderlich.'),
                ]);
            }

            $ermittlungsjahr = max($anschaffungsjahr, $steuerjahr);
            $ermittlungsjahrForCalculation = min($ermittlungsjahr, $baujahr + 75);
            $alterOriginal = max(0, $ermittlungsjahr - $baujahr);
            $alter = max(0, $ermittlungsjahrForCalculation - $baujahr);

            $renovationsInput = collect($payload['renovations'] ?? [])
                ->map(fn ($item) => [
                    'category_key' => $item['category_key'] ?? null,
                    'time_window_key' => $item['time_window_key'] ?? null,
                    'extent_percent' => isset($item['extent_percent']) ? (int) $item['extent_percent'] : null,
                ])
                ->filter(fn ($item) => ! empty($item['category_key']))
                ->keyBy('category_key');

            $categories = RenovationCategory::with('timeFactors')->get()->keyBy('key');
            $extentWeights = RenovationExtentWeight::all()->pluck('weight', 'extent_percent');

            [$score, $scoreDetails, $scoreRaw] = $this->calculateScore($categories, $extentWeights, $renovationsInput);

            $contactInput = Arr::only($payload['contact'] ?? [], ['email', 'phone', 'name']);
            if (isset($contactInput['email'])) {
                $contactInput['email'] = strtolower(trim((string) $contactInput['email']));
            }

            $relativeAge = $gnd > 0 ? $alter / $gnd : 0;
            $formula = ScoreFormulaSet::where('score', $score)->first();

            $useAdvancedFormula = false;
            if ($formula && $gnd > 0) {
                $relMin = $formula->rel_alter_min ?? 0;
                $alterThreshold = $formula->alter_schwelle ?? 0;
                $useAdvancedFormula = $relativeAge >= $relMin || $alter >= $alterThreshold;
            }

            $rnd = $this->calculateRnd($useAdvancedFormula ? $formula : null, $alter, $gnd);
            $rndRounded = round($rnd, 2);
            [$rndMin, $rndMax] = $this->buildInterval($rndRounded, $gnd, $relativeAge);
            $afa = $rndRounded > 0 ? round(100 / $rndRounded, 2) : null;
            $afaFrom = $rndMax > 0 ? round(100 / $rndMax, 2) : null;
            $afaTo = $rndMin > 0 ? round(100 / $rndMin, 2) : null;
            $recommendation = $this->buildRecommendation($rndRounded);

            $inputs = [
                'property_type_key' => $propertyType->key,
                'baujahr' => $baujahr,
                'anschaffungsjahr' => $anschaffungsjahr,
                'steuerjahr' => $steuerjahr,
                'ermittlungsjahr' => $ermittlungsjahr,
                'bauweise' => $payload['bauweise'] ?? null,
                'eigennutzung' => isset($payload['eigennutzung']) ? (bool) $payload['eigennutzung'] : null,
                'renovations' => $renovationsInput->map(fn ($item) => Arr::except($item, 'category_key'))->toArray(),
                'address' => Arr::only($payload['address'] ?? [], ['street', 'zip', 'city', 'country']),
                'contact' => $contactInput,
                'notes' => $payload['notes'] ?? null,
            ];

            $debug = [
                'score_raw' => round($scoreRaw, 4),
                'score_rounded' => $score,
                'relative_age' => round($relativeAge, 4),
                'ermittlungsjahr_for_calculation' => $ermittlungsjahrForCalculation,
                'alter_original' => $alterOriginal,
                'alter_capped' => $alter,
                'afa_percent_from' => $afaFrom,
                'afa_percent_to' => $afaTo,
                'afa_percent_single' => $afa,
                'use_advanced_formula' => $useAdvancedFormula,
            ];

            if ($formula) {
                $debug['formula'] = [
                    'score' => (float) $formula->score,
                    'a' => $formula->a !== null ? (float) $formula->a : null,
                    'b' => $formula->b !== null ? (float) $formula->b : null,
                    'c' => $formula->c !== null ? (float) $formula->c : null,
                    'alter_schwelle' => $formula->alter_schwelle,
                    'rel_alter_min' => $formula->rel_alter_min !== null ? (float) $formula->rel_alter_min : null,
                ];
            }

            $calculation = new Calculation([
                'property_type_id' => $propertyType->id,
                'gnd' => $gnd,
                'baujahr' => $baujahr,
                'anschaffungsjahr' => $anschaffungsjahr,
                'steuerjahr' => $steuerjahr,
                'ermittlungsjahr' => $ermittlungsjahr,
                'alter' => $alter,
                'score' => $score,
                'score_details' => $scoreDetails,
                'inputs' => $inputs,
                'result_debug' => $debug,
                'rnd_years' => $rndRounded,
                'rnd_min' => $rndMin,
                'rnd_max' => $rndMax,
                'afa_percent' => $afa,
                'recommendation' => $recommendation,
            ]);

            if ($user) {
                $calculation->user()->associate($user);
            }

            $calculation->save();

            return $calculation->fresh();
        });
    }

    /**
     * @param  Collection<string, array{category_key: ?string, time_window_key: ?string, extent_percent: ?int}>  $renovationsInput
     * @return array{0: float, 1: array<string, array<string, mixed>>, 2: float}
     */
    private function calculateScore(Collection $categories, Collection $extentWeights, Collection $renovationsInput): array
    {
        $totalPoints = 0.0;
        $details = [];

        foreach ($categories as $key => $category) {
            $input = $renovationsInput->get($key);
            $extentPercent = $input['extent_percent'] ?? 0;
            $extentWeight = $extentWeights->get($extentPercent, $extentPercent ? $extentPercent / 100 : 0);
            $timeWindowKey = $input['time_window_key'] ?? 'nicht';
            $timeFactor = optional($category->timeFactors->firstWhere('time_window_key', $timeWindowKey))->factor ?? 0;

            $maxPoints = (float) $category->max_points;
            $points = $maxPoints * (float) $extentWeight * (float) $timeFactor;
            $totalPoints += $points;

            $details[$key] = [
                'label' => $category->label,
                'max_points' => $maxPoints,
                'extent_percent' => $extentPercent,
                'extent_weight' => (float) $extentWeight,
                'time_window_key' => $timeWindowKey,
                'time_factor' => (float) $timeFactor,
                'points' => round($points, 2),
            ];
        }

        $scoreRounded = round($totalPoints * 2) / 2;
        $scoreRounded = round(max(0.0, min(20.0, $scoreRounded)), 1);

        return [$scoreRounded, $details, $totalPoints];
    }

    private function calculateRnd(?ScoreFormulaSet $formula, int $alter, int $gnd): float
    {
        if ($gnd <= 0) {
            return 0.0;
        }

        if ($formula && $formula->a !== null && $formula->b !== null && $formula->c !== null) {
            $advanced = (float) $formula->a * (($alter ** 2) / $gnd)
                - (float) $formula->b * $alter
                + (float) $formula->c * $gnd;
        } else {
            $advanced = null;
        }

        $simple = $gnd - $alter;
        $simple = max(0, $simple);

        $value = $advanced !== null ? $advanced : $simple;
        $value = max(0.0, min($gnd, $value));

        return $value;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function buildInterval(float $rnd, int $gnd, float $relativeAge): array
    {
        if ($rnd <= 0) {
            return [0, 0];
        }

        $step = self::DEFAULT_INTERVAL_STEP;
        $min = (int) max(0, floor($rnd / $step) * $step);
        $max = (int) min($gnd, ceil($rnd / $step) * $step);

        if ($max - $min < $step) {
            $max = min($gnd, $min + $step);
        }

        if ($max < $min) {
            $max = $min;
        }

        return [$min, $max];
    }

    private function buildRecommendation(float $rnd): ?string
    {
        if ($rnd <= 0) {
            return null;
        }

        return $rnd >= 25
            ? __('Gutachten ist sinnvoll, Beauftragung empfehlen')
            : __('Gutachten ist nicht sinnvoll, keine Beauftragung ermöglichen');
    }
}
