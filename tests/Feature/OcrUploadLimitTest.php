<?php

namespace Tests\Feature;

use App\Branch;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OcrUploadLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'status'    => User::STATUS_ACTIVE,
        ]);
    }

    public function test_oversized_upload_returns_actionable_message(): void
    {
        $file = new \Illuminate\Http\UploadedFile(
            __FILE__,
            'record.jpg',
            'image/jpeg',
            UPLOAD_ERR_INI_SIZE,
            false
        );

        $response = $this->actingAs($this->admin)
            ->post('ocr-recognize/recognize', ['image' => $file]);

        $response->assertStatus(200)->assertJson(['status' => 0]);

        $message = $response->json('message');
        $this->assertNotSame('image 上传失败。', $message);
        $this->assertStringContainsString('upload_max_filesize', $message);
    }
}
