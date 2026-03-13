<?php

namespace Poradnik\Platform\Domain\SaasPlans;

if (! defined('ABSPATH')) {
    exit;
}

final class PlanRepository
{
    private const OPTION_KEY = 'poradnik_saas_plans';
    private const USER_META_KEY = 'poradnik_saas_plan';

    /**
     * @var array<string, array{label: string, price: float, currency: string, features: array<int, string>}>
     */
    private const DEFAULT_PLANS = [
        'free' => [
            'label'    => 'FREE',
            'price'    => 0.0,
            'currency' => 'PLN',
            'features' => [
                'Dostęp do portalu',
                'Komentowanie artykułów',
                'Zapisywanie poradników',
            ],
        ],
        'pro' => [
            'label'    => 'PRO',
            'price'    => 49.0,
            'currency' => 'PLN',
            'features' => [
                'Wszystko z FREE',
                'Tworzenie poradników',
                'Panel specjalisty',
                'Statystyki artykułów',
            ],
        ],
        'business' => [
            'label'    => 'BUSINESS',
            'price'    => 199.0,
            'currency' => 'PLN',
            'features' => [
                'Wszystko z PRO',
                'Panel reklamodawcy',
                'Kampanie reklamowe',
                'Faktury i płatności Stripe',
            ],
        ],
        'enterprise' => [
            'label'    => 'ENTERPRISE',
            'price'    => 0.0,
            'currency' => 'PLN',
            'features' => [
                'Wszystko z BUSINESS',
                'Dedykowane wsparcie',
                'Indywidualny onboarding',
                'Umowa SLA',
            ],
        ],
    ];

    /**
     * @return array<string, array{label: string, price: float, currency: string, features: array<int, string>}>
     */
    public static function all(): array
    {
        $stored = get_option(self::OPTION_KEY);
        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        return self::DEFAULT_PLANS;
    }

    /**
     * @return array{label: string, price: float, currency: string, features: array<int, string>}|null
     */
    public static function find(string $planKey): ?array
    {
        $plans = self::all();
        $plan = $plans[$planKey] ?? null;

        return is_array($plan) ? $plan : null;
    }

    public static function getUserPlan(int $userId): string
    {
        if ($userId < 1) {
            return 'free';
        }

        $plan = get_user_meta($userId, self::USER_META_KEY, true);

        if (! is_string($plan) || $plan === '') {
            return 'free';
        }

        $plans = self::all();

        return array_key_exists($plan, $plans) ? $plan : 'free';
    }

    public static function setUserPlan(int $userId, string $planKey): bool
    {
        if ($userId < 1) {
            return false;
        }

        $plans = self::all();
        if (! array_key_exists($planKey, $plans)) {
            return false;
        }

        return (bool) update_user_meta($userId, self::USER_META_KEY, sanitize_key($planKey));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getUsersWithPlan(string $planKey, int $limit = 50): array
    {
        $args = [
            'meta_key'   => self::USER_META_KEY,
            'meta_value' => sanitize_key($planKey),
            'number'     => max(1, min(200, $limit)),
            'fields'     => ['ID', 'user_login', 'user_email', 'display_name'],
        ];

        $users = get_users($args);
        $result = [];

        foreach ($users as $user) {
            $result[] = [
                'id'           => $user->ID,
                'login'        => $user->user_login,
                'email'        => $user->user_email,
                'display_name' => $user->display_name,
                'plan'         => $planKey,
            ];
        }

        return $result;
    }
}
