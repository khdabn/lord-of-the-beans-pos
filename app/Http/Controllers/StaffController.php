<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Requests\StaffRequest;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class StaffController extends Controller
{
    public function index() { return view('staff.index',['members'=>User::withCount('sales')->orderBy('full_name')->paginate(12)]); }
    public function create() { return view('staff.form',['staff'=>new User]); }
    public function store(StaffRequest $request, ImageService $images)
    {
        $data = $request->safe()->only(['username','full_name','password']); $new = null;
        if ($request->hasFile('avatar')) $data['avatar'] = $new = $images->store($request->file('avatar'),'avatar',true);
        try { User::create($data); } catch (\Throwable $e) { $images->delete($new); throw $e; }
        return redirect()->route('staff.index')->with('success','Staff account created.');
    }
    public function edit(User $staff) { return view('staff.form',compact('staff')); }
    public function update(StaffRequest $request, User $staff, ImageService $images)
    {
        $data = $request->safe()->only(['username','full_name']); $new = null;
        if ($request->filled('password')) $data['password'] = $request->validated('password');
        if ($request->boolean('remove_avatar')) $data['avatar'] = null;
        if ($request->hasFile('avatar')) $data['avatar'] = $new = $images->store($request->file('avatar'),'avatar',true);
        try {
            $old = DB::transaction(function () use ($staff,$data) {
                $locked = User::lockForUpdate()->findOrFail($staff->id);
                $old = $locked->avatar; $locked->update($data); return $old;
            });
        } catch (\Throwable $e) { $images->delete($new); throw $e; }
        if (array_key_exists('avatar',$data) && $old !== $data['avatar']) $images->delete($old);
        // auth.session middleware invalidates other sessions after a password change.
        if ($staff->id === $request->user()->id && isset($data['password'])) {
            $request->user()->refresh();
            $request->session()->put('password_hash_web', $request->user()->password);
        }
        return redirect()->route('staff.index')->with('success','Staff account updated.');
    }
    public function destroy(Request $request, User $staff)
    {
        if ($staff->id === $request->user()->id) return back()->withErrors(['staff'=>'You cannot remove the account you are currently using.']);
        DB::transaction(fn () => User::lockForUpdate()->findOrFail($staff->id)->delete());
        return redirect()->route('staff.index')->with('success','Staff access removed. Their previous sales are preserved.');
    }
}
