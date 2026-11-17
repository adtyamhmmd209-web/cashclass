<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\LessonSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCashClass;
use Tests\TestCase;

class AcademicFlowTest extends TestCase
{
    use CreatesCashClass, RefreshDatabase;

    private array $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->group = $this->createCashClass();
    }

    public function test_grade_pages_and_writes_have_been_removed(): void
    {
        $this->actingAs($this->group['teacher'])->get('/walikelas/grades')->assertNotFound();
        $this->post('/walikelas/records/grade', [])->assertNotFound();
        $this->get('/walikelas/dashboard')->assertOk()->assertDontSee('Nilai siswa');
        $this->actingAs($this->group['student'])->get('/student/grades')->assertNotFound();
        $this->get('/student/dashboard')->assertOk()->assertDontSee('Nilai siswa');
    }

    public function test_wali_attendance_and_schedule_pages_have_been_removed(): void
    {
        $this->actingAs($this->group['teacher'])->get('/walikelas/attendance')->assertNotFound();
        $this->get('/walikelas/schedule')->assertNotFound();
        $this->post('/walikelas/records/attendance', [])->assertNotFound();
        $this->post('/walikelas/records/schedule', [])->assertNotFound();
    }

    public function test_announcements_and_existing_student_schedule_are_class_scoped(): void
    {
        $this->actingAs($this->group['teacher'])->post('/walikelas/records/announcement', ['title' => 'Informasi kas', 'content' => 'Pembayaran kas bulan ini.'])->assertRedirect();
        LessonSchedule::create([
            'class_id' => $this->group['class']->id,
            'weekday' => 1,
            'subject' => 'Kegiatan kelas',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
        $this->actingAs($this->group['student'])->get('/student/dashboard')->assertOk()->assertSee('Informasi kas');
        $this->get('/student/schedule')->assertOk()->assertSee('Kegiatan kelas');
        $other = $this->createCashClass('Kelas lain');
        $this->actingAs($other['student'])->get('/student/dashboard')->assertOk()->assertDontSee('Informasi kas');
        $this->get('/student/schedule')->assertOk()->assertDontSee('Kegiatan kelas');
    }

    public function test_other_teacher_cannot_delete_class_announcements(): void
    {
        $record = Announcement::create(['class_id' => $this->group['class']->id, 'user_id' => $this->group['teacher']->id, 'title' => 'Informasi kelas', 'content' => 'Isi pengumuman']);
        $other = $this->createCashClass('Kelas lain');
        $this->actingAs($other['teacher'])->delete('/walikelas/records/announcement/'.$record->id)->assertNotFound();
        $this->actingAs($this->group['teacher'])->delete('/walikelas/records/announcement/'.$record->id)->assertRedirect();
    }

    public function test_navigation_is_rendered_once_in_sidebar_on_all_roles(): void
    {
        foreach ([['treasurer', '/dashboard'], ['teacher', '/walikelas/class'], ['teacher', '/walikelas/reports'], ['student', '/student/dashboard'], ['student', '/profile'], ['treasurer', '/error-reports']] as [$role,$url]) {
            $response = $this->actingAs($this->group[$role])->get($url)->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi utama"]')->length);
            $this->assertSame(0, $xpath->query('//nav[not(ancestor::aside) and not(@aria-label="Pagination Navigation")]')->length);
            $this->assertSame(0, $xpath->query('//a[contains(@href,"/grades")]')->length);
            if ($role === 'teacher') {
                $this->assertSame(0, $xpath->query('//a[contains(@href,"/attendance") or contains(@href,"/schedule")]')->length);
                $this->assertSame(0, $xpath->query('//a[contains(@href,"/walikelas/bendahara")]')->length);
                $this->assertSame($url === '/walikelas/class' ? 1 : 0, $xpath->query('//a[contains(@href,"/walikelas/accounts/create")]')->length);
            }
        }
    }
}
