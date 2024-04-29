<?php


namespace Modules\Notification\Http\Controllers;


use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Entities\Mention;

class MentionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function mentionUser()
    {
        try {
            // return "hello";
            $users = User::where('company_id', auth('api')->user()->company_id)->select('id', 'first_name', "last_name")->get();
            $newData = [];

            foreach ($users as $key => $value) {

                array_push($newData, $value);

                // array_push($newData, $display);
            }


            return response()->json([

                'data' =>  $newData,
                'message'    => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
            ], 500);
        }
    }
    public function index()
    {
    }


    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('notification::create');
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $string = $request->comment;
            // return $string;

            // foreach ($request->mention_Id as $key => $value) {
            //     return $value;
            // }

            $parts = explode(')', $string);
            $updatedString = 0;
            if (count($parts) > 1) {
                $result = trim($parts[count($parts) - 1]);
                $updatedString = $result;
            } else {
                echo "Slash not found!";
            }
            // return  $updatedString;
            foreach ($request->mention_Id as $key => $value) {
                $feeSetting = new Mention();

                $feeSetting->send_user_id  = auth('api')->user()->company_id;

                $feeSetting->received_user_id    = $value;
                $feeSetting->message   = $updatedString;
                $feeSetting->property_id    = $request->property_id;
                $feeSetting->inspection_id   = $request->inspection_id;
                $feeSetting->job_id = $request->job_id;
                $feeSetting->listing_id = $request->listing_id;
                // $feeSetting->maintenance_id = $request->maintenance_id;
                $feeSetting->company_id   = auth('api')->user()->company_id;

                $feeSetting->save();
            }
            return response()->json([
                'message' => 'mention  successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }


    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('notification::show');
    }


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('notification::edit');
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
