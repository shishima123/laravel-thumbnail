<?php

namespace Shishima\Thumbnail\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Shishima\Thumbnail\Tests\Models\Document;
use Shishima\Thumbnail\Tests\TestCase;
use Shishima\Thumbnail\Facade\Thumbnail;
use Shishima\Thumbnail\Models\Thumbnail as ThumbnailModel;

class ThumbnailTest extends TestCase
{
    public function test_can_create_thumbnail()
    {
        $images = ['gif.gif', 'jpg.jpg', 'jpeg.jpeg', 'png.png'];

        foreach ($images as $image)
        {
            $result = Thumbnail::setFile(UploadedFile::fake()->image($image))->create();
            Storage::disk('thumbnail')->assertExists($result['name']);
        }

        $files = ['docx.docx', 'xlsx.xlsx', 'pdf.pdf'];
        foreach ($files as $file)
        {
            $result = Thumbnail::setFile(__DIR__.'/../dummy/'.$file)->create();
            Storage::disk('thumbnail')->assertExists($result['name']);
        }
    }

    public function test_model_event_can_create_thumbnail()
    {
        $this->createRoute();

        Storage::fake('document');
        Storage::fake('temp_thumbnail');
        Storage::fake('thumbnail');

        $fileName = 'avatar.jpg';
        $file     = UploadedFile::fake()->image($fileName);

        $this->post('/document', ['document' => $file]);

        Storage::disk('document')->assertExists($file->hashName());
        static::assertDatabaseHas('thumbnails', ['original_name' => $file->hashName()]);

        $thumbnail = ThumbnailModel::where('original_name', $file->hashName())->latest('id')->first();

        Storage::disk('thumbnail')->assertExists($thumbnail['name']);

    }

    protected function createRoute(): void
    {
        Route::post('/document', function (Request $request)
        {
            $path = $request->file('document')->store('/', 'document');
            $data = ['path' => $path];
            return Document::create($data);
        });
    }
}
