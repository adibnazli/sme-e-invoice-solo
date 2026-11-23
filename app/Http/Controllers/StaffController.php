<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::orderBy('role', 'desc')->orderBy('name', 'asc')->get();

        return view('staff.index', compact('staff'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:6',
            'role' => 'required|in:CASHIER,OWNER',
        ]);

        $user = new User;
        $user->name = $request->name;
        $user->username = $request->username;
        $user->password = Hash::make($request->password);
        $user->role = $request->role;
        $user->save();

        return back()->with('success', "Staff account '{$user->username}' created successfully.");
    }

    public function destroy($id)
    {
        if ((int) $id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own active account.']);
        }

        User::findOrFail($id)->delete();

        return back()->with('success', 'Staff account removed.');
    }
}
