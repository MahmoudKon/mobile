<?php

namespace App\Http\Controllers\Rep;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\NewLineRequest;
use App\Line;
use App\LineCity;
use DB;

class LineController extends Controller
{
    //
    public function store(Request $request)
    {
        $validation_rules = [
            'name' => ['required', 'string', 'min:3', 'max:100', 'unique:lines,name'],
            'representative_id' => ['required', 'exists:users,id'],
            'cities' => ['required', 'array'],
            'cities.*' => ['required', 'exists:line_cities,id']
        ];

        $validation = validator()->make($request->all(), $validation_rules);

        if ($validation->fails()) {

            return response()->json($this->getValidationFailsResponse($validation));
        }

        DB::transaction(function() use($request){
            $line = Line::create([
                'name' => $request->name,
                'representative_id' => $request->representative_id
            ]);

            foreach ($request->cities as $city)
            {
                LineCity::create([
                    'line_id' => $line->id,
                    'city_id' => $city
                ]);
            }

        });

        return response()->json([
            'status' => true
        ]);
    }

    private function getValidationFailsResponse($validation)
    {
        $errors = $validation->errors();
        $error_data = [];
        foreach ($errors->all() as $error) {
            array_push($error_data, $error);
        }
        $data = $error_data;
        return [
            'status' => false,
            'error' => $data,
        ];
    }
}
