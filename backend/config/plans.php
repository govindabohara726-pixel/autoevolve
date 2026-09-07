<?php
return [
    'default' => 'starter',
    'plans' => [
        'starter' => [
            'name' => 'Starter', 'monthly' => 19, 'price_id' => env('STRIPE_PRICE_STARTER'),
            'limits' => ['sites'=>1,'ai_generations'=>30,'ai_improvements'=>60,'evolution_runs'=>30],
        ],
        'growth' => [
            'name' => 'Growth', 'monthly' => 49, 'price_id' => env('STRIPE_PRICE_GROWTH'),
            'limits' => ['sites'=>5,'ai_generations'=>150,'ai_improvements'=>300,'evolution_runs'=>150],
        ],
        'scale' => [
            'name' => 'Scale', 'monthly' => 129, 'price_id' => env('STRIPE_PRICE_SCALE'),
            'limits' => ['sites'=>25,'ai_generations'=>750,'ai_improvements'=>1500,'evolution_runs'=>750],
        ],
    ],
];
