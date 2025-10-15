<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => function () use ($request) {
                $user = $request->user();

                $communities = [];
                if ($user) {
                    $communities = $user->communities()
                        ->select('communities.id', 'communities.name', 'communities.title', 'communities.avatar')
                        ->orderBy('communities.name')
                        ->get()
                        ->map(function ($c) {
                            return [
                                'id' => $c->id,
                                'name' => (str_starts_with($c->name, 'r/') ? $c->name : 'r/' . $c->name),
                                'title' => $c->title,
                                'avatar' => $c->avatar,
                            ];
                        })
                        ->values()
                        ->toArray();
                }

                return [
                    'user' => $user,
                    'communities' => $communities,
                ];
            },
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
