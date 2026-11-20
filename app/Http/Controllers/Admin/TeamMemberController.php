<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Services\AuditLogger;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Practitioner and team profiles shown on the About page. */
class TeamMemberController extends Controller
{
    public function __construct(private ImageUploader $images, private AuditLogger $audit) {}

    public function index()
    {
        return view('admin.team.index', ['members' => TeamMember::ordered()->get()]);
    }

    public function create()
    {
        return view('admin.team.form', ['member' => new TeamMember(['is_published' => false])]);
    }

    public function store(Request $request)
    {
        $member = new TeamMember($this->validated($request));
        $member->image_path = $this->images->fromRequest($request, null, 'team');
        $member->save();
        $this->audit->log('team_member.created', $member, ['name' => $member->name]);

        return redirect()->route('admin.team.index')->with('success', "Profile for {$member->name} created.");
    }

    public function edit(TeamMember $team)
    {
        return view('admin.team.form', ['member' => $team]);
    }

    public function update(Request $request, TeamMember $team)
    {
        $team->fill($this->validated($request, $team));
        $team->image_path = $this->images->fromRequest($request, $team->image_path, 'team');
        $team->save();
        $this->audit->log('team_member.updated', $team, ['name' => $team->name, 'published' => $team->is_published]);

        return redirect()->route('admin.team.index')->with('success', "Profile for {$team->name} saved.");
    }

    public function destroy(TeamMember $team)
    {
        $team->delete();
        $this->audit->log('team_member.deleted', $team, ['name' => $team->name]);

        return redirect()->route('admin.team.index')->with('success', "Profile for {$team->name} deleted.");
    }

    private function validated(Request $request, ?TeamMember $member = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', Rule::unique('team_members', 'slug')->ignore($member?->id)],
            'job_title' => ['nullable', 'string', 'max:150'],
            'qualifications' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_published' => ['boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            ...ImageUploader::fieldRules(),
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['display_order'] = $data['display_order'] ?? 0;

        return $data;
    }
}
