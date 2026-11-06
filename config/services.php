<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Default AI provider used until a user saves their own settings.
    'ai' => [
        'provider' => env('AI_PROVIDER', 'ollama'),
        'base_url' => env('AI_BASE_URL', 'http://localhost:11434'),
        'model' => env('AI_MODEL', 'llama3.2'),
        'api_key' => env('AI_API_KEY'),
        // Endpoint safety (spec 11, Network). Disable local endpoints on public deployments.
        'allow_local_endpoints' => (bool) env('AI_ALLOW_LOCAL_ENDPOINTS', true),
        'allowed_hosts' => env('AI_ALLOWED_HOSTS', ''),
        'max_context_chars' => (int) env('AI_MAX_CONTEXT_CHARS', 6000),
        // Semantic search. Leave AI_EMBEDDING_MODEL empty to use keyword search only.
        'embedding_provider' => env('AI_EMBEDDING_PROVIDER'),
        'embedding_base_url' => env('AI_EMBEDDING_BASE_URL'),
        'embedding_model' => env('AI_EMBEDDING_MODEL'),
        'embedding_api_key' => env('AI_EMBEDDING_API_KEY'),
        // Tuned for nomic-embed-text / gemini-embedding: unrelated text scores ~0.3-0.43.
        'semantic_min_similarity' => (float) env('AI_SEMANTIC_MIN_SIMILARITY', 0.45),
        'semantic_relative_margin' => (float) env('AI_SEMANTIC_RELATIVE_MARGIN', 0.12),
        // Related-note suggestions and knowledge-graph "similar meaning" edges.
        'related_min_similarity' => (float) env('AI_RELATED_MIN_SIMILARITY', 0.58),
        'graph_min_similarity' => (float) env('AI_GRAPH_MIN_SIMILARITY', 0.6),
        'graph_neighbours' => (int) env('AI_GRAPH_NEIGHBOURS', 2),
    ],

];
