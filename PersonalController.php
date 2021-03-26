<?php

namespace App\Http\Controllers;

use App\Models\Amountworker;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Experience;
use App\Models\Favorite;
use App\Models\Question;
use App\Models\Requested;
use App\Models\Image;
use App\Models\Message;
use App\Models\Need;
use App\Models\Other;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Region;
use App\Models\Response;
use App\Models\Review;
use App\Models\Specialization;
use App\Models\Type;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;

class PersonalController extends Controller
{

    public function resource(Request $request)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();

        if (isset($request->scripts)) {
            $view = view('layouts.scripts', ['scripts' => $request->scripts])->toHtml();
            return response()->json($view);
        }
        if (isset($request->get_sub)) {
            $sub = '<option value="" class="op_f">-------</option>';
            if ($request->get_sub != 'null') {
                $sub = '<option value="" class="op_f">-------</option>';
                $subar = Category::where('sub', $request->get_sub)->get()->sortBy('ind');
                $sub_pr = $profile->subcategory_id;
                foreach ($subar as $s) {
                    $sel = '';
                    if ($sub_pr == $s->id) { $sel = 'selected'; }
                    $sub = $sub . '<option value="' . $s->id . '"' . $sel . '>' . $s->title . '</option>';
                }
            }
            return response()->json($sub);

        } elseif (isset($request->cat) or isset($request->subcat) or isset($request->spec)) {
            $companies = Profile::where('confirmed', 1)->where('id', '!=', $profile->id);
            if (isset($request->cat) and $request->cat != 'null') {
                $companies = $companies->where('category_id', $request->cat);
            }
            if (isset($request->subcat) and $request->subcat != 'null') {
                $companies = $companies->where('subcategory_id', $request->subcat);
            }
            if (isset($request->spec) and $request->spec != 'null') {
                $companies = $companies->where('specialization_id', $request->spec);
            }
            $companies = $companies->get();
            $count = $companies->count();
            $view = view('layouts.items', compact('companies'))->toHtml();
            return response()->json([$count, $view]);

        } elseif (isset($request->type)) {
            $questions = Question::where('type_id', $request->type)->get();
            $count = $questions->count();
            $view = view('layouts.questions', compact('questions'))->toHtml();
            return response()->json([$count, $view]);

        } elseif (isset($request->favorite) and isset($profile)) {
            $favorite = Favorite::where('profile_id', $profile->id)->where('company_id', $request->favorite)->first();
            if (!isset($favorite)) {
                $favorite = new Favorite();
                $favorite->profile_id = $profile->id;
                $favorite->company_id = $request->favorite;
                $favorite->save();
                return response()->json(__('control.personal1'));
            } else {
                return response()->json(__('control.personal2'));
            }
        } elseif (isset($request->need) and isset($profile)) {
            if (isset($request->title) and isset($request->text)) {
                $needcategory = new Need();
                $needcategory->name = $request->need;
                $needcategory->profile_id = $profile->id;
                $needcategory->title = $request->title;
                $needcategory->text = $request->text;
                $needcategory->save();
                return response()->json(__('control.personal3'));
            } else {
                return response()->json(__('control.personal4'));
            }
        } elseif (isset($request->complaint) and isset($profile)) {
            $complaint = Profile::where('id', $request->complaint)->first();
            if (isset($complaint) and isset($request->text)) {
                $complaint = new Complaint();
                $complaint->profile_id = $profile->id;
                $complaint->company_id = $request->complaint;
                $complaint->text = $request->text;
                $complaint->save();
                return response()->json(__('control.personal5'));
            } else {
                return response()->json(__('control.personal6'));
            }
        } elseif (isset($request->mails) and isset($profile)) {
            $mails = explode(',', $request->mails);
            foreach ($mails as $mail) {
                $message = new Message();
                $message->profile_id = $profile->id;
                $message->e_mail = $mail;
                $message->subject = __('control.personal7');
                $message->message = __('control.personal8');
                $message->save();
            }
            return response()->json(__('control.personal9'));

        } else {
            return response()->json(__('control.personal10'));
        }
    }

    public function profile_edit(Request $request)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();

        if ($request->isMethod('post')) {
            if (empty($profile)) {
                $profile = new Profile();
                $profile->user_id = Auth::user()->id;
                $profile->confirmed = 1;
            }
            if (!isset($request->name)) {
                $alert = __('control.personal4');
                return redirect()->route('profile.edit', compact('alert'));
            }
            $profile->name = $request->name;
            $profile->address = $request->address;
            $profile->phone_whatsapp = $request->phone_whatsapp;
            $profile->email = $request->email;
            $profile->category_id = $request->category;
            $profile->subcategory_id = $request->subcategory;
            $profile->specialization_id = $request->specialization_id;
            $profile->specialization_list = $request->specialization;
            if (isset($request->image)) {
                $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image));
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
                file_put_contents($tmpFilePath, $fileData);
                $tmpFile = new File($tmpFilePath);
                $file = new UploadedFile(
                    $tmpFile->getPathname(),
                    $tmpFile->getFilename(),
                    $tmpFile->getMimeType(),
                    0,
                    true
                );
                $path = $file->store('image', 'public');
                $profile->image = $path;
            }
            if (isset($request->pdf_empty) and $request->pdf_empty == 'empty') {
                $profile->pdf = null;
            } else {
                if (isset($request->pdf)) {
                    $fileData = base64_decode(preg_replace('#^data:application/pdf;base64,#i', '', $request->pdf));
                    $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
                    file_put_contents($tmpFilePath, $fileData);
                    $tmpFile = new File($tmpFilePath);
                    $file = new UploadedFile(
                        $tmpFile->getPathname(),
                        $tmpFile->getFilename(),
                        $tmpFile->getMimeType(),
                        0,
                        true
                    );
                    $path = $file->store('pdf', 'public');
                    $profile->pdf = $path;
                }
            }
            $profile->contact_person = $request->contact_person;
            $profile->contact_phone = $request->contact_phone;
            $profile->contact_phone_d = $request->contact_phone_d;
            $profile->telegram = $request->telegram;
            $profile->viber = $request->viber;
            $profile->description = $request->description;
            if (isset($request->regions)) {
                $regions = '';
                foreach ($request->regions as $region) {
                    $regions = $regions . '|' . $region;
                }
                $profile->regions = $regions;
            }
            $profile->site = $request->site;
            $profile->experience = $request->experience;
            $profile->amountworkers = $request->amountworkers;
            if (isset($request->others)) {
                $others = '';
                foreach ($request->others as $other) {
                    $others = $others . '|' . $other;
                }
                $profile->others = $others;
            }
            if (isset($request->time1)) {
                $profile->mode_week = $request->starttime1 . '|' . $request->endtime1;
            } else {
                $profile->mode_week = null;
            }
            if (isset($request->time2)) {
                $profile->mode_sat = $request->starttime2 . '|' . $request->endtime2;
            } else {
                $profile->mode_sat = null;
            }
            if (isset($request->time3)) {
                $profile->mode_alw = 1;
            } else {
                $profile->mode_alw = 0;
            }
            if (isset($request->time4)) {
                $profile->shabat = 1;
            } else {
                $profile->shabat = 0;
            }
            $profile->save();

            if (isset($profile->id)) {
                if (isset($request->reviews_del)) {
                    Review::wherein('id', $request->reviews_del)->delete();
                }
                if (isset($request->reviews)) {
                    foreach ($request->reviews as $review) {
                        $fileData = base64_decode(preg_replace('#^data:application/pdf;base64,#i', '', $review));
                        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
                        file_put_contents($tmpFilePath, $fileData);
                        $tmpFile = new File($tmpFilePath);
                        $file = new UploadedFile(
                            $tmpFile->getPathname(),
                            $tmpFile->getFilename(),
                            $tmpFile->getMimeType(),
                            0,
                            true
                        );
                        $path = $file->store('review', 'public');
                        if (isset($path)) {
                            $rew = new Review();
                            $rew->profile_id = $profile->id;
                            $rew->file_name = $file->getClientOriginalName();
                            $rew->path = $path;
                            $rew->save();
                        }
                    }
                }
                if (isset($request->image_del)) {
                    Image::wherein('id', $request->image_del)->delete();
                }
                if (isset($request->gallery)) {
                    $i = 0;
                    foreach ($request->gallery as $gallery) {
                        $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $gallery));
                        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
                        file_put_contents($tmpFilePath, $fileData);
                        $tmpFile = new File($tmpFilePath);
                        $file = new UploadedFile(
                            $tmpFile->getPathname(),
                            $tmpFile->getFilename(),
                            $tmpFile->getMimeType(),
                            0,
                            true
                        );
                        $path = $file->store('gallery', 'public');
                        if (isset($path)) {
                            $img = new Image();
                            $img->profile_id = $profile->id;
                            if (isset($request->titles[$i])) {
                                $img->file_name = $request->titles[$i];
                            } else {
                                $img->file_name = $file->getClientOriginalName();
                            }
                            $img->path = $path;
                            $img->save();
                        }
                        $i++;
                    }
                }
            }

            $alert = __('control.personal11');
            return redirect()->route('profile', compact('alert'));

        } else {
            $categories = Category::where('sub', 0)->get()->sortBy('ind');
            $specializations = Specialization::all()->sortBy('ind');
            $regions = Region::all()->sortBy('ind')->groupBy('sub');
            $exps = Experience::all()->sortBy('ind');
            $amounts = Amountworker::all()->sortBy('ind');
            $others = Other::all()->sortBy('ind')->groupBy('sub');
            return view('edit_profile', compact('profile', 'categories', 'specializations', 'regions', 'exps', 'amounts', 'others'));
        }
    }

    public function profile(Request $request)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        return view('profile', compact('profile'));
    }

    public function payment(Request $request)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        return view('payment', compact('profile'));
    }

    public function company(Request $request, $id)
    {
        $profile = Profile::where('id', $id)->first();
        if (isset($profile)) {
            if ($profile->user_id == Auth::user()->id) {
                return redirect()->route('profile');
            } else {
                $view = View::where('user_id', Auth::user()->id)->where('company_id', $id)->latest()->first();
                if (!isset($view) or strtotime(date('Y-m-d H:i:s')) - strtotime($view->created_at) > 86400) {
                    $view = new View();
                    $view->user_id = Auth::user()->id;
                    $view->company_id = $id;
                    $view->save();

                    $profile->views += 1;
                    $profile->save();
                }
                $company = 'true';
                return view('profile', compact('profile', 'company'));
            }
        } else {
            return redirect()->back();
        }
    }

    public function new_project(Request $request)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        if ($request->isMethod('post') and isset($profile)) {

            if (isset($request->title) and isset($request->text) and isset($request->type)) {
                if (isset($request->project)) {
                    $project = Project::where('id', $request->project)->where('profile_id', $profile->id)->first();
                }
                if (isset($project)) {
                    $note = __('control.personal12');
                    Requested::where('profile_id', $profile->id)->where('project_id', $project->id)->update(['read' => 1]);
                } else {
                    $project = new Project();
                    $project->profile_id = $profile->id;
                    $note = __('control.personal13');

                    $profile->num_proj = Project::where('profile_id', $profile->id)->count() + 1;
                    $profile->save();
                }
                $project->title = $request->title;
                $project->text = $request->text;
                $project->type_id = $request->type;
                if (isset($request->regions)) {
                    $regions = '';
                    foreach ($request->regions as $region) {
                        $regions = $regions . '|' . $region;
                    }
                    $project->regions = $regions;
                }
                if (isset($request->contact_hide)) {
                    $project->contact_hide = 1;
                } else {
                    $project->contact_hide = 0;
                }
                $project->docs_url = $request->docs_url;
                if (isset($request->questions)) {
                    $questions = '';
                    foreach ($request->questions as $question) {
                        if (strstr($question, 'name|||')) {
                            $questions = $questions . '<p><strong>' . explode('name|||', $question)[1] . '</strong></p>';
                        } else {
                            $questions = $questions . ' ' . $question;
                        }
                    }
                    $project->questions = $questions;
                }
                $project->save();

                return response()->json([1, $note, $project->id, $project->questions]);
            } else {
                return response()->json([0, __('control.personal4')]);
            }

        } else {
            $categories = Category::where('sub', 0)->get()->sortBy('ind');
            $regions = Region::all()->sortBy('ind')->groupBy('sub');
            $specializations = Specialization::all()->sortBy('ind');
            $types = Type::all()->sortBy('ind');

            if (isset($request->project)) {
                $project = Project::where('id', $request->project)->where('profile_id', $profile->id)->first();
            } else {
                $project = null;
            }

            return view('new_project', compact('categories', 'regions', 'specializations', 'project', 'types'));
        }
    }

    public function find(Request $request)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        $companies = Profile::where('confirmed', 1)->where('id', '!=', $profile->id);
        if (isset($request->category) and $request->specialization != 'null') {
            $companies = $companies->where('category_id', $request->category);
            if (isset($request->subcategory) and $request->specialization != 'null') {
                $companies = $companies->where('subcategory_id', $request->subcategory);
            }
        }
        if (isset($request->specialization) and $request->specialization != 'null') {
            $companies = $companies->where('specialization_id', $request->specialization);
        }
        $companies = $companies->get();

        $categories = Category::where('sub', 0)->get()->sortBy('ind');
        $specializations = Specialization::all()->sortBy('ind');
        return view('find', compact('categories', 'specializations', 'companies', 'request'));
    }

    public function requested(Request $request)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        if ($request->isMethod('post') and isset($profile)) {
            if (isset($request->project)) {
                if (isset($request->company)) {
                    $i = 0;
                    foreach ($request->project as $project) {
                        $p = Project::where('id', $project)->where('profile_id', $profile->id)->where('status', 0)->first();
                        if (isset($p)) {
                            foreach ($request->company as $company) {
                                $c = Profile::where('id', $company)->first();
                                if (isset($c)) {
                                    $f = Requested::where('profile_id', $profile->id)->where('project_id', $project)->where('company_id', $company)->where('status', 0)->first();
                                    if (!isset($f)) {
                                        $requested = new Requested();
                                        $requested->profile_id = $profile->id;
                                        $requested->project_id = $project;
                                        $requested->company_id = $company;
                                        $requested->save();
                                        $i++;
                                    }
                                }
                            }
                        }
                    }
                    if ($i == 0) {
                        return response()->json([0, __('control.personal14')]);
                    } else {
                        return response()->json([1, __('control.personal15')]);
                    }
                } else {
                    return response()->json([0, __('control.personal16')]);
                }
            } else {
                return response()->json([0, __('control.personal17')]);
            }

        } else {
            $categories = Category::where('sub', 0)->get()->sortBy('ind');;
            $regions = Region::all()->sortBy('ind')->groupBy('sub');
            $specializations = Specialization::all()->sortBy('ind');
            $companies = Profile::where('confirmed', 1)->where('id', '!=', $profile->id);
            if (isset($request->company)) {
                $companies = $companies->whereIn('id', $request->company);
            }
            $companies = $companies->get();
            $projects = Project::where('profile_id', $profile->id)->where('status', 0)->get();

            return view('requested', compact('categories', 'regions', 'specializations', 'projects', 'companies', 'request'));
        }
    }

    public function projects(Request $request, $proj = null)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        if (isset($request->delete)) {
            $project = Project::where('id', $request->delete)->where('profile_id', $profile->id)->first();
            if (isset($project)) {
                $project->status = 1;
                $project->save();
                $alert = __('control.personal18');
            } else {
                $alert = __('control.personal10');
            }
            return redirect()->route('projects', compact('alert'));
        } elseif (isset($request->return)) {
            $project = Project::where('id', $request->return)->where('profile_id', $profile->id)->first();
            if (isset($project)) {
                $project->status = 0;
                $project->save();
                $alert = __('control.personal19');
            } else {
                $alert = __('control.personal10');
            }
            return redirect()->route('projects', ['proj' => $project->id, 'alert' => $alert]);
        } elseif (isset($request->reject)) {
            $response = Response::where('id', $request->reject)->where('company_id', $profile->id)->first();
            if (isset($response)) {
                if ($response->status == 0) {
                    $response->status = 1;
                    $alert = __('control.personal20');
                } elseif ($response->status == 1) {
                    $response->status = 0;
                    $alert = __('control.personal21');
                }
                $response->save();
            } else {
                $alert = __('control.personal23');
            }
            return redirect()->route('projects', ['proj' => $response->project_id, 'alert' => $alert]);
        } elseif (isset($request->select)) {
            $response = Response::where('id', $request->select)->where('company_id', $profile->id)->first();
            if (isset($response)) {
                if ($response->status == 0) {
                    $response->status = 2;
                    $alert = __('control.personal22');
                } elseif ($response->status == 2) {
                    $response->status = 0;
                    $alert = __('control.personal23');
                }
                $response->save();
            } else {
                $alert = __('control.personal10');
            }
            return redirect()->route('projects', ['proj' => $response->project_id, 'alert' => $alert]);
        } elseif (isset($proj)) {
            $project = Project::where('id', $proj)->where('profile_id', $profile->id)->first();
            $price = Response::where('project_id', $project->id)->whereNotNull('price')->avg('price');
            return view('project', compact('project', 'price'));
        } else {
            $projects = Project::where('profile_id', $profile->id)->get()->groupBy('status');
            return view('projects', compact('projects'));
        }
    }

    public function requests(Request $request, $proj = null)
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        if ($request->isMethod('post') and isset($profile)) {
            if (isset($proj) and isset($request->text)) {
                $p = Project::where('id', $proj)->where('status', 0)->first();
                if (isset($p)) {
                    $r = Response::where('profile_id', $profile->id)->where('project_id', $p->id)->where('company_id', $p->profile_id)->count();
                    $response = new Response();
                    $response->profile_id = $profile->id;
                    $response->project_id = $p->id;
                    $response->company_id = $p->profile_id;
                    $response->text = $request->text;
                    $response->price = $request->price;
                    if (isset($request->pdf)) {
                        $path = $request->file('pdf')->store('pdf', 'public');
                        $response->pdf = $path;
                    }
                    $response->save();

                    if ($r == 0) {
                        $requested = Requested::where('company_id', $profile->id)->where('project_id', $proj)->where('status', 0)->first();
                        $profile->resp_time = round($profile->resp_time + (strtotime($response->created_at) - strtotime($requested->created_at)) / 60);
                        $profile->resp_num = $profile->resp_num + 1;
                        $profile->save();
                    }

                    return response()->json([1, __('control.personal24')]);
                } else {
                    return response()->json([0, __('control.personal25')]);
                }
            } else {
                return response()->json([0, __('control.personal4')]);
            }
        } else {
            if (isset($request->delete)) {
                $requested = Requested::where('project_id', $request->delete)->where('company_id', $profile->id)->first();
                if (isset($requested)) {
                    $requested->status = 1;
                    $requested->save();
                    $alert = __('control.personal26');
                } else {
                    $alert = __('control.personal10');
                }
                return redirect()->route('requests', compact('alert'));
            } elseif (isset($request->return)) {
                $requested = Requested::where('project_id', $request->return)->where('company_id', $profile->id)->first();
                if (isset($requested)) {
                    $requested->status = 0;
                    $requested->save();
                    $alert = __('control.personal27');
                } else {
                    $alert = __('control.personal10');
                }
                return redirect()->route('requests', compact('alert'));
            } elseif (isset($proj)) {
                $project = Project::where('id', $proj)->first();
                if (isset($project)) {
                    $view = View::where('user_id', Auth::user()->id)->where('project_id', $proj)->latest()->first();
                    if (!isset($view) or strtotime(date('Y-m-d H:i:s')) - strtotime($view->created_at) > 86400) {
                        $view = new View();
                        $view->user_id = Auth::user()->id;
                        $view->project_id = $proj;
                        $view->save();

                        $project->views += 1;
                        $project->save();
                    }
                    $requested = Requested::where('project_id', $proj)->where('company_id', $profile->id)->first();
                    $requested->read = 0;
                    $requested->save();
                    return view('offer', compact('project'));
                } else {
                    return redirect()->back();
                }
            } else {
                $requs = Requested::where('company_id', $profile->id)->get();
                return view('requests', compact('requs'));
            }
        }
    }
}
