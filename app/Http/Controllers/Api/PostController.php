<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $data['posts'] = Post::all();
        return response()->success('All post data retrieved successfully', $data['posts']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // Validate request data
        $validateUser = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'user_id' => 'required',
                'image' => 'required|mimes:png,jpg,jpeg,gif'
            ]
        );

        if ($validateUser->fails()) {

            return response()->error('Error Occured while uploading', $validateUser->errors()->all(), 401);

        }

        // Handle image upload
        $img = $request->file('image'); // use file() to retrieve the file
        $ext = $img->getClientOriginalExtension();
        $imageName = time() . '.' . $ext;

        // Make sure 'uploads' directory exists
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Move the uploaded file
        if (!$img->move($uploadPath, $imageName)) {
            return response()->error('Error Occured while uploading', $validateUser->errors()->all(), 401);

        }

        // Save post data to the database
        $postdata = Post::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => $request->user_id,
            'image' => $imageName // Store relative path in the database
        ]);

        if ($postdata) {
            return response()->success('All post data retrieved successfully', $postdata);
        }
        return response()->error('Error Occured while uploading', $validateUser->errors()->all(), 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $data['post'] = Post::select(
            'id',
            'title',
            'description',
            'image'
        )->where(['id' => $id])->get();

        return response()->success('post found', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate request data
        $validateUser = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'user_id' => 'required',
                'image' => 'nullable|mimes:png,jpg,jpeg,gif' // Image is optional for update
            ]
        );

        if ($validateUser->fails()) {
            return response()->error('Error occurred', $validateUser->errors()->all(), 401);

        }

        // Fetch the post by ID
        $postUpdate = Post::findOrFail($id);  // Correct way to find a post

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $path = public_path('uploads');

            // If there's an old image, delete it
            if ($postUpdate->image && file_exists($path . '/' . $postUpdate->image)) {
                unlink($path . '/' . $postUpdate->image);
            }

            // Upload the new image
            $img = $request->file('image');
            $ext = $img->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;

            // Make sure 'uploads' directory exists
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            // Move the uploaded file to the 'uploads' directory
            $img->move($path, $imageName);

        } else {
            // If no new image, keep the old image
            $imageName = $postUpdate->image;
        }

        // Update the post in the database
        $postUpdate->update([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => $request->user_id,
            'image' => 'uploads/' . $imageName  // Store the relative image path
        ]);

        // Return the updated post data
        return response()->success('Post Updated Successfully', $postUpdate);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find the post by ID
        $post = Post::findOrFail($id);

        // Path to the image file
        $path = public_path('uploads/' . $post->image);

        // If the post has an image and the file exists, delete it
        if ($post->image && file_exists($path)) {
            unlink($path);
        }

        // Delete the post from the database
        if ($post->delete()) {
            return response()->success('Post and its image deleted successfully', $post);

        }

        // If the deletion failed
        return response()->error('Failed to delete post', $post->errors()->all(), 401);


    }

}
