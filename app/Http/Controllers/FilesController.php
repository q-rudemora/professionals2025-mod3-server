<?php

namespace App\Http\Controllers;

use App\Models\Accesses;
use App\Models\Files;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FilesController extends Controller
{
    public function upload(Request $request) {
        $validator = Validator::make($request->all(), [
            "files" => ['required', 'array'],
            "files.*" => ['file', 'max:4096', 'mimes:doc,pdf,docx,zip,jpeg,jpg,png'],
        ]);
        // 422 error, if fails validation
        if($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }
        $validate = $validator->validate();
        $res = [];
        foreach($request->file('files') as $i=> $file) {
            if(!$request->file('files')[$i]->isValid()) {
                $res[] = [
                    "success" => false,
                    "message" => "File not loaded",
                    "name" => $file->getClientOriginalName(), // get original name uploaded file
                ];
            }
            $new_file = Files::create([
                "file_id" => Str::random(10),
                "url" => $file->store('uploads', 'public'),
                "name" => $file->getClientOriginalName().$request->user()->id.Carbon::now(),
            ]);
            Accesses::create([
                "users_id" => $request->user()->id,
                "files_id" => $new_file->id,
                "fullname" => "{$request->user()->first_name} {$request->user()->last_name}",
                "type" => "author",
            ]);
            $res[] = [
                "success" => true,
                "message" => "Success",
                "name" => $new_file->name,
                "url" => "http://127.0.0.1:8000/api/files/".$new_file->file_id,
            ];
        }
        return response()->json($res, 200);
    }
    public function update(String $file_id, Request $request) {
        
        
        $validator = Validator::make($request->all(), [
            "name" => ['required','string', 'unique:files'],
        ]);
        // 422 error, if fails validation
        if($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }
        $validate = $validator->validate();
        $file = Files::where('file_id', $file_id)->first();
        if(!$file) {
            return response()->json([
                "message" => "Not found",
            ], 404);
        }
        $access = Accesses::where('accesses.users_id', $request->user()->id)->where('accesses.files_id', $file->id)->first();
        if($access->type != "author") {
            return response()->json([
                "message" => "Forbidden for you",
            ], 403);
        }
        $file->name = $validate['name'];
        $file->save();
        return response()->json([
            "success" => true,
            "message" => "Renamed",
        ], 200);
    }

    public function delete(String $file_id, Request $request) {
        $file = Files::where('file_id', $file_id)->first();
        if(!$file) {
            return response()->json([
                "message" => "Not found",
            ], 404);
        }
        $access = Accesses::where('accesses.users_id', $request->user()->id)->where('accesses.files_id', $file->id)->first();
        if($access->type != "author") {
            return response()->json([
                "message" => "Forbidden for you",
            ], 403);
        }
        $file->delete();
        return response()->json([
            "success" => true,
            "message" => "File already deleted",
        ], 200);
    }


    public function download(String $files_id, Request $request) {
        $file = Files::where('file_id', $files_id)->first();
        if(!$file) {
            return response()->json([
                "message" => "Not found",
            ], 404);
        }
        $access = Accesses::where('accesses.users_id', $request->user()->id)->where('accesses.files_id', $file->id)->first();

        if($access->type != "author") {
            return response()->json([
                "message" => "Forbidden for you",
            ], 403);
        }
        return redirect()->to("http://127.0.0.1:8000/storage/".$file->url);
    }


    // For Accesses
    public function store(String $files_id, Request $request) {
        $file = Files::where('file_id', $files_id)->first();
        if(!$file) {
            return response()->json([
                "message" => "Not found",
            ], 404);
        }
        $access = Accesses::where('accesses.users_id', $request->user()->id)->where('accesses.files_id', $file->id)->first();

        if($access->type != "author") {
            return response()->json([
                "message" => "Forbidden for you",
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            "email" => ['required', 'email'],
        ]);
        // 422 error, if fails validation
        if($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }
        $validate = $validator->validate();
        $user = User::where('users.email', $validate['email'])->first();
        Accesses::create([
            "files_id" => $file->id,
            "users_id" => $user->id,
            "fullname" => "{$request->user()->first_name} {$request->user()->last_name}",
            "type" => "co-author",
        ]);
        $accesses = Accesses::where('accesses.files_id', $file->id)->with(['users'])->get();
        return response()->json([
            $accesses->map(function($access) {
                return [
                    "fullname" => "{$access->users->first_name} {$access->users->last_name}",
                    "email" => $access->users->email,
                    "type" => $access->type,
                ];
            })
        ], 200);
    }

    public function ac_delete(String $files_id, Request $request) {
        $file = Files::where('file_id', $files_id)->first();
        if(!$file) {
            return response()->json([
                "message" => "Not found",
            ], 404);
        }
        $access = Accesses::where('accesses.users_id', $request->user()->id)->where('accesses.files_id', $file->id)->first();

        if($access->type != "author") {
            return response()->json([
                "message" => "Forbidden for you",
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            "email" => ['required', 'email'],
        ]);
        // 422 error, if fails validation
        if($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => $validator->errors(),
            ], 422);
        }
        $validate = $validator->validate();
        $user = User::where('users.email', $validate['email'])->first();
        $access = Accesses::where('accesses.users_id', $user->id)->first();
        $access->delete();
        $accesses = Accesses::where('accesses.files_id', $file->id)->with(['users'])->get();
        return response()->json([
            $accesses->map(function($access) {
                return [
                    "fullname" => "{$access->users->first_name} {$access->users->last_name}",
                    "email" => $access->users->email,
                    "type" => $access->type,
                ];
            })
        ], 200);
    }

    public function disk(Request $request) {
        $accesses = Accesses::with(['files'])->where("accesses.users_id", $request->user()->id)->get();
        $response = [
            $accesses->map(function($acces) {
                $accesses = Accesses::where('accesses.files_id', $acces->files->id)->with(['users'])->get();
                return [
                    "file_id" => $acces->files->file_id,
                    "name" => $acces->files->name,
                    "url" => "http://127.0.0.1:8000/api/files/".$acces->files->file_id,
                    "accesses" => $accesses->map(function($acces) {
                        return [
                            "fullname" => "{$acces->users->first_name} {$acces->users->last_name}",
                            "email" => $acces->users->email,
                            "type" => $acces->type,
                        ];
                    }),
                ];
            })
        ];
        return response()->json($response, 200);
    }

    public function shared(Request $request) {
        $accesses = Accesses::with(['files'])->where("accesses.users_id", $request->user()->id)->get();
        $response = [
            $accesses->map(function($acces) {
                return [
                    "file_id" => $acces->files->file_id,
                    "name" => $acces->files->name,
                    "url" => "http://127.0.0.1:8000/api/files/".$acces->files->file_id,
                    
                ];
            })
        ];
        return response()->json($response, 200);
    }

}
