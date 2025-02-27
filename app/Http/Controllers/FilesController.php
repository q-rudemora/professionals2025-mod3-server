<?php

namespace App\Http\Controllers;

use App\Models\Accesses;
use App\Models\Files;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
                "url" => base_path().'/uploads/'.$new_file->url,
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
        $access = Accesses::where('accesses.users_id', $request->user()->id)->where('accesses.files_id', $file->id)->first();
        if($access->type != "author") {
            return response()->json([
                "message" => "Forbidden for you",
            ], 403);
        }
        if(!$file) {
            return response()->json([
                "message" => "Not found",
            ], 404);
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
        $access = Accesses::where('accesses.users_id', $request->user()->id)->where('accesses.files_id', $file->id)->first();
        if($access->type != "author") {
            return response()->json([
                "message" => "Forbidden for you",
            ], 403);
        }
        if(!$file) {
            return response()->json([
                "message" => "Not found",
            ], 404);
        }

        $file->delete();
        return response()->json();
    }

}
