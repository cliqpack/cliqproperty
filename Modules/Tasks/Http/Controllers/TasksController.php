<?php

namespace Modules\Tasks\Http\Controllers;

use DateTime;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Settings\Entities\LabelSetting;
use Modules\Tasks\Entities\Task;
use Modules\Tasks\Entities\TaskDoc;

class TasksController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index()
    {
        $date = date("Y-m-d");
        try {
            $activeTask = Task::where('company_id', auth('api')->user()->company_id)->where('status', 'pending')->with('label')->get();
            $dueTask = Task::where('company_id', auth('api')->user()->company_id)->where('due_by', '<', $date)->where('status', 'due')->with('label')->get();
            $dueLaterTask = Task::where('company_id', auth('api')->user()->company_id)->where('due_by', '>', $date)->where('status', 'due_later')->with('label')->get();
            $closedTask = Task::where('company_id', auth('api')->user()->company_id)->where('status', 'closed')->with('label')->get();
            $columns = [
                ["id" => 1, "title" => "Due", "cards" => $dueTask],
                ["id" => 2, "title" => "Active", "cards" => $activeTask],
                ["id" => 3, "title" => "Due Later", "cards" => $dueLaterTask]
            ];

            return response()->json([
                'columns'    => $columns,
                'closedTask' => ['data' => $closedTask],
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

    public function taskClosedListForApp()
    {
        try {
            $closedTask = Task::where('company_id', auth('api')->user()->company_id)->where('status', 'closed')->with('label')->get();
            return response()->json([

                'data' =>  $closedTask,
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

    public function showTaskActivity($id)
    {
        try {
            // $jobActivity = PropertyActivity::where('maintenance_id', $id)->with('task', 'inspection', 'maintenance', 'listing', 'property_activity_email')->get();
            $taskActivity = PropertyActivity::where('task_id', $id)->with(['task',  'property_activity_email' => function ($query) {
                $query->where('email_status', 'pending');
            }])->get();
            return response()->json([
                "data" => $taskActivity,
                "message" => "Successful"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function active_ssr(Request $request)
    {
        // return "heloo";
        $date = date("Y-m-d");
        try {
            $page_qty = $request->sizePerPage;
            $activeTask = [];
            $activeTaskAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            $task = new Task();
            if ($request->q != 'null') {
                $properties = DB::table('tasks')->join('properties', 'properties.id', '=', 'tasks.property_id')->groupBy('tasks.property_id')->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('tasks.property_id');
                $managers = DB::table('tasks')->join('users', 'users.id', '=', 'tasks.manager_id')->groupBy('tasks.manager_id')->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('tasks.manager_id');
                $contacts = DB::table('tasks')->join('contacts', 'contacts.id', '=', 'tasks.contact_id')->groupBy('tasks.contact_id')->where('contacts.reference', 'like', '%' . $request->q . '%')->pluck('tasks.contact_id');

                $activeTask = $task->where('company_id', auth('api')->user()->company_id)
                    ->whereNotIN('status', ['Closed'])
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $activeTaskAll = $task->where('company_id', auth('api')->user()->company_id)
                    ->whereNotIN('status', ['Closed'])
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $activeTask = $task->where('company_id', auth('api')->user()->company_id)->whereNotIN('status', ['Closed'])->with('label')->offset($offset)->limit($page_qty)->get();
                $activeTaskAll = $task->where('company_id', auth('api')->user()->company_id)->whereNotIN('status', ['Closed'])->with('label')->get();
            }

            $date = Carbon::now()->format('Y-m-d');

            $due = $task->where('company_id', auth('api')->user()->company_id)->whereNotIN('status', ['Closed'])->where('due_by', '<', $date)->get();

            $due = count($due);


            return response()->json([
                'data' => $activeTask,
                'message'    => 'Successfull',
                'length' => count($activeTaskAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'due'=>$due,
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

    public function active()
    {
        $date = date("Y-m-d");
        try {
            $activeTask = Task::where('company_id', auth('api')->user()->company_id)->whereNotIN('status', ['Closed'])->with('label')->get();

            return response()->json([
                'data' => $activeTask,
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

    public function dueTask(Request $request)
    {
        $date = date("Y-m-d");
        try {
            $page_qty = $request->sizePerPage;
            $dueTask = [];
            $dueTaskAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            $task = new Task();
            if ($request->q != 'null') {
                $properties = DB::table('tasks')->join('properties', 'properties.id', '=', 'tasks.property_id')->groupBy('tasks.property_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('tasks.property_id');
                $managers = DB::table('tasks')->join('users', 'users.id', '=', 'tasks.manager_id')->groupBy('tasks.manager_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('tasks.manager_id');
                $contacts = DB::table('tasks')->join('contacts', 'contacts.id', '=', 'tasks.contact_id')->groupBy('tasks.contact_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('contacts.reference', 'like', '%' . $request->q . '%')->pluck('tasks.contact_id');

                $dueTask = $task->where('company_id', auth('api')->user()->company_id)
                    ->where('due_by', '<', $date)
                    ->where('status', 'due')
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $dueTaskAll = $task->where('company_id', auth('api')->user()->company_id)
                    ->where('due_by', '<', $date)->where('status', 'due')
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $dueTask = $task->where('company_id', auth('api')->user()->company_id)->where('due_by', '<', $date)->where('status', 'due')->with('label')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $dueTaskAll = $task->where('company_id', auth('api')->user()->company_id)->where('due_by', '<', $date)->where('status', 'due')->with('label')->get();
            }

            $date = Carbon::now()->format('Y-m-d');

            $due = $task->where('company_id', auth('api')->user()->company_id)->whereNotIN('status', ['Closed'])->where('due_by', '<', $date)->get();

            $due = count($due);

            return response()->json([
                'data' => $dueTask,
                'message'    => 'Successfull',
                'length' => count($dueTaskAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'due'=>$due,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function dueLaterTask()
    {
        $date = date("Y-m-d");
        try {
            $dueLaterTask = Task::where('company_id', auth('api')->user()->company_id)->where('due_by', '>', $date)->where('status', 'due_later')->with('label')->get();


            return response()->json([
                'data' => $dueLaterTask,
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

    public function dueLaterTaskSsr(Request $request)
    {

        $date = date("Y-m-d");
        try {
            $page_qty = $request->sizePerPage;
            $dueTask = [];
            $dueTaskAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            $task = new Task();
            if ($request->q != 'null') {
                $properties = DB::table('tasks')->join('properties', 'properties.id', '=', 'tasks.property_id')->groupBy('tasks.property_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('tasks.property_id');
                $managers = DB::table('tasks')->join('users', 'users.id', '=', 'tasks.manager_id')->groupBy('tasks.manager_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('tasks.manager_id');
                $contacts = DB::table('tasks')->join('contacts', 'contacts.id', '=', 'tasks.contact_id')->groupBy('tasks.contact_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('contacts.reference', 'like', '%' . $request->q . '%')->pluck('tasks.contact_id');

                $dueLaterTask = $task->where('company_id', auth('api')->user()->company_id)
                    ->where('due_by', '>', $date)
                    ->where('status', 'due_later')
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $dueLaterTaskAll = $task->where('company_id', auth('api')->user()->company_id)
                    ->where('due_by', '>', $date)->where('status', 'due_later')
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $dueLaterTask = $task->where('company_id', auth('api')->user()->company_id)->where('due_by', '<', $date)->where('status', 'due_later')->with('label')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $dueLaterTaskAll = $task->where('company_id', auth('api')->user()->company_id)->where('due_by', '<', $date)->where('status', 'due_later')->with('label')->get();
            }

            $date = Carbon::now()->format('Y-m-d');

            $due = $task->where('company_id', auth('api')->user()->company_id)->whereNotIN('status', ['Closed'])->where('due_by', '<', $date)->get();

            $due = count($due);

            return response()->json([
                'data' => $dueLaterTask,
                'message'    => 'Successfull',
                'length' => count($dueLaterTaskAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'due'=>$due,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function closedTaskSsr(Request $request)
    {

        $date = date("Y-m-d");
        try {
            $page_qty = $request->sizePerPage;
            $closedTask = [];
            $closedTaskAll = [];

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            $task = new Task();
            if ($request->q != 'null') {
                $properties = DB::table('tasks')->join('properties', 'properties.id', '=', 'tasks.property_id')->groupBy('tasks.property_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('tasks.property_id');
                $managers = DB::table('tasks')->join('users', 'users.id', '=', 'tasks.manager_id')->groupBy('tasks.manager_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('tasks.manager_id');
                $contacts = DB::table('tasks')->join('contacts', 'contacts.id', '=', 'tasks.contact_id')->groupBy('tasks.contact_id')->where('tasks.company_id', auth('api')->user()->company_id)->where('contacts.reference', 'like', '%' . $request->q . '%')->pluck('tasks.contact_id');

                $closedTask = $task->where('company_id', auth('api')->user()->company_id)
                    ->where('status', 'closed')
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $closedTaskAll = $task->where('company_id', auth('api')->user()->company_id)
                    ->where('status', 'closed')
                    ->where('summary', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due_by', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('contact_id', $contacts)
                    ->with('label')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $closedTask = $task->where('company_id', auth('api')->user()->company_id)->where('status', 'closed')->with('label')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $closedTaskAll = $task->where('company_id', auth('api')->user()->company_id)->where('status', 'closed')->with('label')->get();
            }

            $date = Carbon::now()->format('Y-m-d');

            $due = $task->where('company_id', auth('api')->user()->company_id)->whereNotIN('status', ['Closed'])->where('due_by', '<', $date)->get();

            $due = count($due);

            return response()->json([
                'data' => $closedTask,
                'length' => count($closedTaskAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message'    => 'Successfull',
                'due'=>$due,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('tasks::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $date = date("Y-m-d");
        try {
            $attriuteName = array(
                'property_id' =>  $request->property_id,
                'contact_id'  =>  $request->contact_id,
                'manager_id'  =>  $request->manager_id,
                'company_id'  =>  auth('api')->user()->company_id,
                'due_by'      =>  $request->due_by,
                'summary'     =>  $request->summary,
                'description' =>  $request->description

            );
            $validator = Validator::make($attriuteName, [
                'property_id' => 'required',
                'summary'     => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(array("error" => $validator->getMessageBag()->toArray()));
            } else {
                $taskId = null;

                DB::transaction(function () use ($attriuteName, $request, &$taskId, $date) {
                    $tasks = new Task();

                    $tasks->property_id = $request->property_id;
                    $tasks->contact_id  = $request->contact_id;
                    $tasks->manager_id  = $request->manager_id;
                    $tasks->company_id  = auth('api')->user()->company_id;
                    $tasks->due_by      = $request->due_by;
                    if ($request->due_by === null) {
                        $tasks->status = 'pending';
                    } elseif ($request->due_by < $date) {
                        $tasks->status = 'due';
                    } elseif ($request->due_by > $date) {
                        $tasks->status = 'due_later';
                    }
                    $tasks->summary = $request->summary;
                    $tasks->description = $request->description;
                    $tasks->save();
                    $property = Properties::where('id', $request->property_id)->first();
                    $po = $property->owner_id != null ? $property->owner_id : null;
                    $pt = $property->tenant_id != null ? $property->tenant_id : null;
                    // return $po;
                    // $taskActivity = new PropertyActivity();
                    // $taskActivity->property_id = $request->property_id;
                    // $taskActivity->contact_id = $request->contact_id;
                    // $taskActivity->owner_contact_id = $po;
                    // $taskActivity->tenant_contact_id = $pt;
                    // $taskActivity->task_id = $tasks->id;
                    // $taskActivity->type = 'redirect';
                    // $taskActivity->status = 'Pending';
                    // $taskActivity->save();
                    $taskId = $tasks->id;

                    $message_action_name = "Task";

                    $messsage_trigger_point = 'New Task Added';
                    $data = [
                        "property_id" => $request->property_id,
                        "id" => $taskId,
                        'date' => $date,
                        "owner_contact_id" => $po,
                        "tenant_contact_id" => $pt

                    ];
                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                    // $value = $activityMessageTrigger->trigger();
                    $value = $activityMessageTrigger->trigger();
                    // return $value;
                });

                return response()->json([
                    'tasks_id' => $taskId,
                    'message'  => 'successfull'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'false',
                'error'   => ['error'],
                'message' => $th->getMessage(),
                'data'    => []
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
        try {
            $task = Task::where('company_id', auth('api')->user()->company_id)->where('id', $id)->with('taskdoc', 'label')->first();
            // $label =  LabelSetting::where('company_id', auth('api')->user()->company_id)->where('type', 'Task')->get();
            $total_due = $task->due_by;
            $today = date('Y-m-d');
            // return $today;\
            $due_date = new DateTime($total_due);
            $today_date = new DateTime($today);
            // Calculate the date difference
            if ($due_date < $today_date) {
                $interval = $today_date->diff($due_date);
                $days_difference = $interval->days;
                $task->days_difference = $days_difference;
                $task->due_status = "Overdue";
            } elseif ($due_date > $today_date) {
                $interval = $today_date->diff($due_date);
                $days_difference = $interval->days;
                $task->days_difference = $days_difference;
                $task->due_status = "Due on";
            } elseif ($due_date == $today_date) {
                $task->days_difference = null;
                $task->due_status = "Due today";
            }
            // return $days_difference;
            return response()->json([
                'data'    => $task,
                // 'label'  => $label,
                // 'total_due_dates' => $days_difference,
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status"  => false,
                "error"   => ['error'],
                "message" => $ex->getMessage(),
                "data"    => []
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('tasks::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $attriuteName = array(
                'property_id' =>  $request->property_id,
                'contact_id'  =>  $request->contact_id,
                'manager_id'  =>  $request->manager_id,
                'company_id'  =>  auth('api')->user()->company_id,
                'due_by'      =>  $request->due_by,
                'summary'     =>  $request->summary,
                'description' =>  $request->description

            );
            $validator = Validator::make($attriuteName, [
                'property_id' => 'required',
                'summary'     => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(array("error" => $validator->getMessageBag()->toArray()));
            } else {
                $tasks = Task::findOrFail($id);
                $tasks->property_id = $request->property_id;
                $tasks->contact_id  = $request->contact_id;
                $tasks->manager_id  = $request->manager_id;
                $tasks->company_id  = auth('api')->user()->company_id;
                $tasks->due_by      = $request->due_by;
                $tasks->summary     = $request->summary;
                $tasks->description = $request->description;
                $tasks->save();
                return response()->json([
                    'tasks_id' => $tasks->id,
                    'message'  => 'successfull'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'false',
                'error'   => ['error'],
                'message' => $th->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $property_activity = PropertyActivity::where('task_id', $id)->delete();
            $task = Task::findOrFail($id);
            $task->delete();
            return response()->json([
                'data'    => $task,
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status"  => false,
                "error"   => ['error'],
                "message" => $ex->getMessage(),
                "data"    => []
            ], 500);
        }
    }

    public function editStatus(Request $request, $id)
    {
        try {
            $tasks = Task::findOrFail($id);
            $tasks->status = $request->status;
            $tasks->complete_date = $request->complete_date;
            $tasks->save();
            // $activity = PropertyActivity::where('task_id', $id)->update(["status" => $request->status]);
            $activity = PropertyActivity::where('task_id', $id)->first();
            $activity->status = $request->status;
            $propertyId = $activity->property_id;
            $tenantId = $activity->tenant_contact_id;
            $message_action_name = "Task";

            $messsage_trigger_point = $request->status;
            $data = [
                "property_id" => $propertyId,

                "tenant_contact_id" => $tenantId,
                "id" => $id

            ];
            $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");


            $value = $activityMessageTrigger->trigger();
            // return $value;
            return response()->json(['message' => 'successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'false',
                'error'   => ['error'],
                'message' => $th->getMessage(),
                'data'    => []
            ], 500);
        }
    }
    public function kanbanEditStatus(Request $request, $item_id)
    {
        try {
            if ($request->to == 1) {
                $yesterday = Carbon::yesterday()->toDateString();
                Task::where('id', $request->item_id)->update(['due_by' => $yesterday, 'status' => 'due']);
                return response()->json(['message' => 'successfull', 'data' => $yesterday,], 200);
            } elseif ($request->to == 2) {
                $now = Carbon::now()->toDateString();
                Task::where('id', $request->item_id)->update(['due_by' => $now, 'status' => 'pending']);
                return response()->json(['message' => 'successfull', 'data' => $now,], 200);
            } elseif ($request->to == 3) {
                $tomorrow = Carbon::tomorrow()->toDateString();
                Task::where('id', $request->item_id)->update(['due_by' => $tomorrow, 'status' => 'due_later']);
                return response()->json([
                    'message' => 'successfull',
                    'data' => $tomorrow,
                ], 200);
            }
            // $tasks = Task::findOrFail($id);
            // $tasks->status = $request->status;
            // $tasks->complete_date = $request->complete_date;
            // $tasks->save();
            // return response()->json(['message' => 'successfull','data'=>$yesterday,], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }
    public function taskEditStatus(Request $request, $item_id)
    {
        try {
            // return "hello";
            if ($request->to == "due") {
                // return "due";
                $yesterday = Carbon::yesterday()->toDateString();
                Task::where('id', $request->item_id)->update(['due_by' => $yesterday, 'status' => 'due']);
                return response()->json(['message' => 'successfull', 'data' => $yesterday,], 200);
            } elseif ($request->to == 'pending') {
                // return "pending";
                $now = Carbon::now()->toDateString();
                Task::where('id', $request->item_id)->update(['due_by' => $now, 'status' => 'pending']);
                return response()->json(['message' => 'successfull', 'data' => $now,], 200);
            } elseif ($request->to == 'due_later') {
                // return "due later";
                $tomorrow = Carbon::tomorrow()->toDateString();
                Task::where('id', $request->item_id)->update(['due_by' => $tomorrow, 'status' => 'due_later']);
                return response()->json([
                    'message' => 'successfull',
                    'data' => $tomorrow,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function uploadTaskFile(Request $request)
    {
        try {
            // return $request->all();
            $imageUpload = new InspectionTaskMaintenanceDoc();
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                $fileSize = $file->getSize();
                // $file->move(public_path('public/Image'), $filename);
                $path = config('app.asset_s') . '/Image';
                $filename_s3 = Storage::disk('s3')->put($path, $file);
                // $imageUpload->property_image = $filename_s3;
                $imageUpload->doc_path = $filename_s3;
                $imageUpload->property_id = $request->property_id;
                $imageUpload->task_id = $request->task_id;
                $imageUpload->file_size = $fileSize;
                $imageUpload->company_id     = auth('api')->user()->company_id;
            }
            $imageUpload->save();

            // $imagePath = config('app.api_url_server') . $filename;
            $imagePath = config('app.api_url_server') . $filename_s3;

            return response()->json([
                'data' => $imagePath,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function getTaskDoc($id)
    {
        try {
            $taskDoc = InspectionTaskMaintenanceDoc::where('task_id', $id)->with(['property' => function ($query) {
                $query->addSelect('id', 'reference');
            }])->get();
            return response()->json([
                'data' => $taskDoc,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
