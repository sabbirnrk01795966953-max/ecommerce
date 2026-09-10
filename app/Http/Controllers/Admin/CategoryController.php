<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\SlugService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){ return view('admin.categories.index',['categories'=>Category::query()->orderBy('sort_order')->paginate(30)]); }
    public function create(){ return view('admin.categories.form',['category'=>new Category]); }
    public function store(Request $r){ $data=$this->data($r); $data['slug']=SlugService::unique(SlugService::normalizeOrGenerate($data['slug']??null,$data['name_en']?:$data['name_bn'],'category'), Category::class); Category::create($data); return redirect()->route('admin.categories.index')->with('success','Category created.'); }
    public function edit(Category $category){ return view('admin.categories.form',compact('category')); }
    public function update(Request $r,Category $category){ $data=$this->data($r,$category); $data['slug']=SlugService::unique(SlugService::normalizeOrGenerate($data['slug']??null,$data['name_en']?:$data['name_bn'],'category'), Category::class, $category->id); $category->update($data); return redirect()->route('admin.categories.index')->with('success','Category updated.'); }
    public function destroy(Category $category){ $category->delete(); return back()->with('success','Category deleted.'); }
    private function data(Request $r,?Category $category=null):array{return $r->validate(['name_bn'=>'required|string|max:160','name_en'=>'nullable|string|max:160','slug'=>['nullable','string','max:500'],'icon'=>'nullable|string|max:20','sort_order'=>'nullable|integer|min:0','is_active'=>'nullable|boolean'])+['is_active'=>$r->boolean('is_active'),'sort_order'=>(int)$r->input('sort_order',0)];}
}
