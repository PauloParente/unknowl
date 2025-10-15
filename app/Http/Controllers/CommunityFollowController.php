<?php

namespace App\Http\Controllers;

use App\Models\Community;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunityFollowController extends Controller
{
    public function store(Request $request, int $communityId): RedirectResponse
    {
        $user = $request->user();
        $community = Community::findOrFail($communityId);
        $user->communities()->syncWithoutDetaching([$community->id]);

        return back()->with('status', 'followed');
    }

    public function destroy(Request $request, int $communityId): RedirectResponse
    {
        $user = $request->user();
        $community = Community::findOrFail($communityId);
        $user->communities()->detach($community->id);

        return back()->with('status', 'unfollowed');
    }
}
