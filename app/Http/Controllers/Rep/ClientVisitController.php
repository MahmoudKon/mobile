<?php

namespace App\Http\Controllers\Rep;

use App\ExceptionalVisitation;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Line;
use App\LineClient;
class ClientVisitController extends Controller
{
    //
    public function store(Request $request)
    {
        $line_id = Line::where('representative_id', auth()->guard('rep')->user()->id)->first()->id;

        $validation_rules = [
            'client_id' => ['required', 'exists:line_clients,client_id,line_id,' . $line_id],
            'date' => ['required', 'date']
        ];

        $validation = validator()->make($request->all(), $validation_rules);

        if ($validation->fails()) {

            return response()->json($this->getValidationFailsResponse($validation));
        }

        $desired_day = strtolower(date('D', strtotime($request->date)));
        $is_in_line = LineClient::where([ ['line_id' , $line_id ], ['client_id', $request->client_id] ])->first()->value($desired_day);
        $visitation_exists = ExceptionalVisitation::where([ ['client_id', $request->client_id], ['line_id', $line_id], ['date', $request->date] ])->first();
        
        if($is_in_line == 1 || $visitation_exists)
        {
            return response()->json([
                'status' => false,
                'error' => ['client is inline for desired day']
            ]);
        }

        ExceptionalVisitation::create([
            'line_id' => $line_id,
            'client_id' => $request->client_id,
            'date' => $request->date
        ]);

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
