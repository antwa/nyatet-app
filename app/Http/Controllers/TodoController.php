<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TodoController extends Controller
{
    public function index()
    {
        $datas = Todo::whereUserId(auth()->user()->id)->whereDate('created_at', Carbon::today())->get();

        return view('todo.index', [
            'title' => 'Todo List',
            'datas' => $datas,
            'modalDelete' => true,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'content' => ['required', 'string'],
        ]);
        $validatedData['user_id'] = auth()->user()->id;
        $validatedData['slug'] = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 10);
        $validatedData['content'] = Crypt::encryptString($validatedData['content']);
        $validatedData['date'] = date('Y-m-d');
        try {
            Todo::create($validatedData);

            return back()->with('status', 'List Berhasil Ditambahkan.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return back()->with('err', '[500] Server Error');
        }
    }

    public function update(Todo $todo)
    {
        if ($todo->user_id == auth()->user()->id) {
            try {
                $todo->update(['is_done' => true]);

                return back();
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                return back()->with('err', '[500] Server Error');
            }
        } else {
            return to_route('todo.index')->with('err', 'Anda Tidak Memiliki Akses.');
        }
    }

    public function destroy(Todo $todo)
    {
        if ($todo->user_id == auth()->user()->id) {
            try {
                $todo->delete();

                return back()->with('status', 'List Berhasil Dihapus.');
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                return back()->with('err', '[500] Server Error');
            }
        } else {
            return to_route('todo.index')->with('err', 'Anda Tidak Memiliki Akses.');
        }
    }

    public function history()
    {
        $todos = Todo::whereUserId(auth()->user()->id)->whereDate('created_at', '!=', Carbon::today())->latest()->paginate(20);
        $datas = $todos->groupBy('date');

        return view('todo.history', [
            'title' => 'History List',
            'datas' => $datas,
            'paginate' => $todos,
            'modalDelete' => true,
        ]);
    }
}
