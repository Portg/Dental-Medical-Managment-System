<?php

namespace Tests\Unit;

use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * 超过 post_max_size 时 PHP 会丢掉**整个请求体**：$_POST 和 $_FILES 全空。
 * 于是校验器只会说「请上传图片」—— 而用户明明选了文件，只会一遍遍重选。
 * 这条路径只能靠 Content-Length 与 post_max_size 比对反推。
 */
class UploadLimitDetectionTest extends TestCase
{
    /** @test */
    public function it_converts_php_ini_size_notation_to_bytes(): void
    {
        $this->assertSame(8 * 1024 * 1024, FunctionsHelper::iniSizeToBytes('8M'));
        $this->assertSame(512 * 1024, FunctionsHelper::iniSizeToBytes('512K'));
        $this->assertSame(1024 * 1024 * 1024, FunctionsHelper::iniSizeToBytes('1G'));
        $this->assertSame(2048, FunctionsHelper::iniSizeToBytes('2048'));
        $this->assertSame(0, FunctionsHelper::iniSizeToBytes(''));
    }

    /** @test */
    public function a_discarded_request_body_is_reported_as_a_size_problem(): void
    {
        $postMax = FunctionsHelper::iniSizeToBytes((string) ini_get('post_max_size'));
        $this->assertGreaterThan(0, $postMax, 'post_max_size 未设置，这条用例无从判断');

        // 模拟 PHP 丢掉请求体之后的样子：没有文件、没有 POST 字段，但 Content-Length 超限
        $request = Request::create('/ocr-recognize', 'POST');
        $request->server->set('CONTENT_LENGTH', $postMax + 1024);

        $this->assertSame(
            (string) ini_get('post_max_size'),
            FunctionsHelper::exceededUploadLimit($request, 'image'),
            '应当报出 post_max_size —— 那才是真正挡住这次上传的限制'
        );
    }

    /**
     * 反向守住：普通的「忘了选文件」不能被误判成体积超限，
     * 否则用户会被指去压缩一个根本不存在的文件。
     */
    /** @test */
    public function a_plain_missing_file_is_not_reported_as_a_size_problem(): void
    {
        $request = Request::create('/ocr-recognize', 'POST', ['patient_id' => 1]);

        $this->assertNull(FunctionsHelper::exceededUploadLimit($request, 'image'));
    }

    /** @test */
    public function a_valid_upload_is_not_reported_as_a_size_problem(): void
    {
        $request = Request::create('/ocr-recognize', 'POST', [], [], [
            'image' => UploadedFile::fake()->image('x.jpg'),
        ]);

        $this->assertNull(FunctionsHelper::exceededUploadLimit($request, 'image'));
    }
}
