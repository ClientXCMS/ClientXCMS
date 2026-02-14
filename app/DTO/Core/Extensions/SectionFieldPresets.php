<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\DTO\Core\Extensions;

/**
 * Reusable field presets for section configuration.
 *
 * Each method returns an array of SectionField instances that can be spread
 * into a section's fields list. This eliminates the massive duplication of
 * badge/title/subtitle, feature1..N, stat1..N, etc. across sections.
 */
trait SectionFieldPresets
{
    /**
     * Header fields: badge + title + subtitle.
     * Present in nearly every section (~45+ times in original JSON).
     *
     * @param  string|null  $hintPrefix  If set, adds hint keys like "theme::sections.{$hintPrefix}.config.badge_hint"
     * @return SectionField[]
     */
    public static function headerFields(?string $hintPrefix = null): array
    {
        return [
            SectionField::text(
                'badge',
                'theme::sections.config.badge',
                hint: $hintPrefix ? "theme::sections.{$hintPrefix}.config.badge_hint" : null,
            ),
            SectionField::text(
                'title',
                'theme::sections.config.title',
                hint: $hintPrefix ? "theme::sections.{$hintPrefix}.config.title_hint" : null,
            ),
            SectionField::textarea(
                'subtitle',
                'theme::sections.config.subtitle',
                rows: 2,
                hint: $hintPrefix ? "theme::sections.{$hintPrefix}.config.subtitle_hint" : null,
            ),
        ];
    }

    /**
     * Feature fields: generates N groups of (icon + title + ?description).
     * Replaces the feature1..6 pattern duplicated across ~15 features sections.
     *
     * @param  int  $count  Number of features (1-based)
     * @param  bool  $withDescription  Whether to include a description textarea per feature
     * @param  array  $iconDefaults  Override default icons, keyed by 1-based index
     * @return SectionField[]
     */
    public static function featureFields(
        int $count = 6,
        bool $withDescription = true,
        array $iconDefaults = [],
    ): array {
        $defaultIcons = [
            1 => 'bi-lightning-charge',
            2 => 'bi-shield-check',
            3 => 'bi-headset',
            4 => 'bi-arrow-repeat',
            5 => 'bi-sliders',
            6 => 'bi-geo-alt',
        ];

        $fields = [];
        for ($i = 1; $i <= $count; $i++) {
            $icon = $iconDefaults[$i] ?? $defaultIcons[$i] ?? 'bi-star';

            $fields[] = SectionField::icon(
                "feature{$i}_icon",
                "theme::sections.features.config.feature{$i}_icon",
                $icon,
            );

            $fields[] = SectionField::text(
                "feature{$i}_title",
                "theme::sections.features.config.feature{$i}_title",
            );

            if ($withDescription) {
                $fields[] = SectionField::textarea(
                    "feature{$i}_description",
                    "theme::sections.features.config.feature{$i}_description",
                    rows: 2,
                );
            }
        }

        return $fields;
    }

    /**
     * Feature fields with a number/metric per item.
     * Used by features_numbers sections which add a "number" field to each feature.
     *
     * @param  int  $count  Number of features
     * @param  array  $numberDefaults  Default values for the number fields, keyed by 1-based index
     * @param  array  $iconDefaults  Override default icons, keyed by 1-based index
     * @return SectionField[]
     */
    public static function featureNumberFields(
        int $count = 6,
        array $numberDefaults = [],
        array $iconDefaults = [],
    ): array {
        $defaultIcons = [
            1 => 'bi-lightning-charge',
            2 => 'bi-shield-check',
            3 => 'bi-headset',
            4 => 'bi-arrow-repeat',
            5 => 'bi-sliders',
            6 => 'bi-geo-alt',
        ];

        $defaultNumbers = [
            1 => '99.9%',
            2 => '24/7',
            3 => '<5min',
            4 => '100%',
            5 => '100+',
            6 => '3',
        ];

        $fields = [];
        for ($i = 1; $i <= $count; $i++) {
            $icon = $iconDefaults[$i] ?? $defaultIcons[$i] ?? 'bi-star';
            $number = $numberDefaults[$i] ?? $defaultNumbers[$i] ?? '';

            $fields[] = SectionField::icon(
                "feature{$i}_icon",
                "theme::sections.features.config.feature{$i}_icon",
                $icon,
            );

            $fields[] = SectionField::text(
                "feature{$i}_number",
                "theme::sections.features.config.feature{$i}_number",
                translatable: false,
                default: $number,
            );

            $fields[] = SectionField::text(
                "feature{$i}_title",
                "theme::sections.features.config.feature{$i}_title",
            );

            $fields[] = SectionField::textarea(
                "feature{$i}_description",
                "theme::sections.features.config.feature{$i}_description",
                rows: 2,
            );
        }

        return $fields;
    }

    /**
     * Stat fields: generates N groups of (value + label).
     * Replaces stat1..4 pattern duplicated across 4+ stats sections.
     *
     * @param  int  $count  Number of stats
     * @param  array  $valueDefaults  Override default values, keyed by 1-based index
     * @return SectionField[]
     */
    public static function statFields(int $count = 4, array $valueDefaults = []): array
    {
        $defaults = [1 => '10K+', 2 => '99.9%', 3 => '24/7', 4 => '5+'];

        $fields = [];
        for ($i = 1; $i <= $count; $i++) {
            $fields[] = SectionField::text(
                "stat{$i}_value",
                "theme::sections.stats.config.stat{$i}_value",
                translatable: false,
                default: $valueDefaults[$i] ?? $defaults[$i] ?? '',
            );

            $fields[] = SectionField::text(
                "stat{$i}_label",
                "theme::sections.stats.config.stat{$i}_label",
            );
        }

        return $fields;
    }

    /**
     * Testimonial fields: generates N groups of (text + author + role).
     * Replaces testimonial1..3 pattern duplicated across 4+ testimonial sections.
     *
     * @param  int  $count  Number of testimonials
     * @return SectionField[]
     */
    public static function testimonialFields(int $count = 3): array
    {
        $fields = [];
        for ($i = 1; $i <= $count; $i++) {
            $fields[] = SectionField::textarea(
                "testimonial{$i}_text",
                "theme::sections.testimonials.config.testimonial{$i}_text",
                rows: 3,
            );

            $fields[] = SectionField::text(
                "testimonial{$i}_author",
                "theme::sections.testimonials.config.testimonial{$i}_author",
            );

            $fields[] = SectionField::text(
                "testimonial{$i}_role",
                "theme::sections.testimonials.config.testimonial{$i}_role",
            );
        }

        return $fields;
    }

    /**
     * Step fields: generates N groups of (title + desc + icon).
     * Replaces step1..3 pattern duplicated across 3 steps sections.
     *
     * @param  int  $count  Number of steps
     * @param  array  $iconDefaults  Override default icons, keyed by 1-based index
     * @return SectionField[]
     */
    public static function stepFields(int $count = 3, array $iconDefaults = []): array
    {
        $defaults = [1 => 'bi-person-plus', 2 => 'bi-gear', 3 => 'bi-rocket-takeoff'];

        $fields = [];
        for ($i = 1; $i <= $count; $i++) {
            $fields[] = SectionField::text(
                "step{$i}_title",
                "theme::sections.steps.config.step{$i}_title",
            );

            $fields[] = SectionField::textarea(
                "step{$i}_desc",
                "theme::sections.steps.config.step{$i}_desc",
                rows: 2,
            );

            $fields[] = SectionField::icon(
                "step{$i}_icon",
                "theme::sections.steps.config.step{$i}_icon",
                $iconDefaults[$i] ?? $defaults[$i] ?? 'bi-star',
            );
        }

        return $fields;
    }

    /**
     * Hero CTA fields: primary + secondary call to action buttons.
     *
     * @return SectionField[]
     */
    public static function heroCTAFields(): array
    {
        return [
            SectionField::text(
                'primary_cta',
                'theme::sections.hero.config.primary_cta',
            ),
            SectionField::text(
                'secondary_cta',
                'theme::sections.hero.config.secondary_cta',
            ),
        ];
    }

    /**
     * Showcase hero fields: hero icon + title + description (used by features_showcase).
     *
     * @return SectionField[]
     */
    public static function showcaseHeroFields(): array
    {
        return [
            SectionField::icon(
                'hero_icon',
                'theme::sections.features.config.hero_icon',
                'bi-lightning-charge',
            ),
            SectionField::text(
                'hero_title',
                'theme::sections.features.config.hero_title',
            ),
            SectionField::textarea(
                'hero_description',
                'theme::sections.features.config.hero_description',
                rows: 2,
            ),
        ];
    }
}
