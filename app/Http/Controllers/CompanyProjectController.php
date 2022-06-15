<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectRole;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class CompanyProjectController extends Controller
{
  public function getAllProjects(Request $request)
  {
    $id = $request->input('id');
    $limit = $request->input('limit', 5);
    $active = $request->input('active');


    if ($id) {
      $project = Project::find($id);

      if ($project) {
        return ResponseFormatter::success($project, 'Project found');
      } else {
        return ResponseFormatter::error(null, 'Project not found', 404, "{$id} not found");
      }
    }
    // return Auth::user();
    $project = Project::where('company_id', Auth::user()->company_id)->latest();

    return ResponseFormatter::success($project->paginate($limit), "Data Successfully Retrieved");
  }

  public function createProject(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'description' => 'required',
    ]);

    if ($validator->fails()) {
      return ResponseFormatter::error(null, 'Validation Error', 400, $validator->errors());
    }
    $validatedData = $validator->validated();

    if ($request->hasFile('image')) {
      $file = $request->file('image');
      $fileName = Str::random(10) . '.' . $file->getClientOriginalExtension();
      $file->move(public_path('images/project'), $fileName);
      $validatedData['images'] =  "\images\project\\" . $fileName;
    } else {
      $validatedData['images'] =  "\images\project\\" . "default.png";
    }

    $validatedData["company_id"] = Auth::user()->company_id;

    $project = Project::create($validatedData);

    return ResponseFormatter::success(Project::find($project->id), "Project has been created");
  }

  public function updateProject(Request $request, $id)
  {

    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'description' => 'required',
    ]);

    if ($validator->fails()) {
      return ResponseFormatter::error(null, 'Validation Error', 400, $validator->errors());
    }

    $validatedData = $validator->validated();

    $project = Project::find($id);

    if ($project) {
      if (Auth::user()->company_id != $project->company_id) {
        return ResponseFormatter::error(null, 'You are not in this company', 401, "You are not in this company");
      }
      if ($request->hasFile('image')) {
        File::exists(public_path() . $project->images) && $project->images != '/images/project/default.png' ? File::delete(public_path() . $project->images) : '';
        $file = $request->file('image');
        $fileName = Str::random(10) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images/project'), $fileName);
        $validatedData['images'] =  "\images\project\\" . $fileName;
      } else {
        $validatedData['images'] =  "\images\project\\" . "default.png";
      }

      $project->update($validatedData);

      return ResponseFormatter::success(Project::find($project->id), "Project has been updated");
    } else {
      return ResponseFormatter::error(null, "Project not found", 404, "Project {$id} not found");
    }
  }

  public function addProjectRole(Request $request, $id)
  {
    // return $request['extra-question'];
    $project = Project::find($id);

    if (Auth::user()->company_id != $project->company_id) {
      return ResponseFormatter::error(null, 'You are not in this company', 401, "You are not in this company");
    }

    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'quota' => 'required|integer',
      'description' => 'required',
    ]);

    if ($validator->fails()) {
      return ResponseFormatter::error(null, 'Validation Error', 400, $validator->errors());
    }

    $data = $request->all();

    $data['project_id'] = $id;

    // return $data;

    $projectRole = ProjectRole::create($data);
    return ResponseFormatter::success(ProjectRole::find($projectRole->id), "Project Role has been created");
  }

  public function getAllApplicants(Request $request)
  {
  }
}
