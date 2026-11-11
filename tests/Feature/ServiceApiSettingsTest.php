<?php

namespace Tests\Feature;

use App\Filament\Pages\ServiceApiSettings;
use App\Models\User;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\ServiceTestCase;

class ServiceApiSettingsTest extends ServiceTestCase
{
    private const ENV_TOKEN = 'environment-test-token-12345678901234567890';
    private const NEW_TOKEN = 'database-test-token-12345678901234567890123';
    private const REPORT = '/api/v1/service/reports/daily?date=2026-10-30';

    protected function setUp(): void
    {
        parent::setUp();
        config(['service_reporting.api_token' => self::ENV_TOKEN]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_existing_installations_use_the_environment_token(): void
    {
        $this->withToken(self::ENV_TOKEN)->getJson(self::REPORT)->assertOk();
        $this->assertTrue(CompanySetting::first()->serviceReportApiEnabled());
        $this->assertNull(CompanySetting::first()->service_report_api_token);
    }

    public function test_only_authenticated_administrators_with_service_enabled_can_configure_the_api(): void
    {
        $this->get('/admin/service-api-settings')->assertRedirect('/admin/login');
        $this->asServiceOperator()->get('/admin/service-api-settings')->assertRedirect('/admin/login');
        $this->assertFalse(ServiceApiSettings::canAccess());

        $this->actingAs(User::factory()->create());
        $page = Livewire::test(ServiceApiSettings::class)->assertOk();
        CompanySetting::first()->update(['enable_service_module' => false]);
        $this->get('/admin/service-api-settings')->assertForbidden();
        $page->call('save')->assertForbidden();
    }

    public function test_token_replacement_is_encrypted_hidden_and_immediately_used_by_the_api(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(ServiceApiSettings::class)
            ->assertFormSet(['enabled' => true, 'token' => ''])
            ->assertDontSee(self::ENV_TOKEN)
            ->fillForm(['enabled' => true, 'token' => self::NEW_TOKEN])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertFormSet(['enabled' => true, 'token' => ''])
            ->assertDontSee(self::NEW_TOKEN);

        $settings = CompanySetting::first();
        $storedToken = DB::table('company_settings')->value('service_report_api_token');
        $this->assertNotSame(self::NEW_TOKEN, $storedToken);
        $this->assertSame(self::NEW_TOKEN, $settings->service_report_api_token);
        $this->assertArrayNotHasKey('service_report_api_token', $settings->toArray());
        $this->assertArrayNotHasKey('service_report_api_token', $settings->attributesToArray());
        $this->withToken(self::NEW_TOKEN)->getJson(self::REPORT)->assertOk();
        $this->withToken(self::ENV_TOKEN)->getJson(self::REPORT)->assertUnauthorized();
        Livewire::test(ServiceApiSettings::class)->assertFormSet(['token' => ''])->assertDontSee(self::NEW_TOKEN);
    }

    public function test_blank_save_preserves_the_token_and_disabling_overrides_the_environment(): void
    {
        $this->actingAs(User::factory()->create());
        $page = Livewire::test(ServiceApiSettings::class)
            ->fillForm(['enabled' => true, 'token' => self::NEW_TOKEN])->call('save')->assertHasNoFormErrors()
            ->fillForm(['enabled' => false, 'token' => ''])->call('save')->assertHasNoFormErrors();

        $this->assertSame(self::NEW_TOKEN, CompanySetting::first()->serviceReportApiToken());
        $this->withToken(self::NEW_TOKEN)->getJson(self::REPORT)->assertStatus(503);
        $this->withToken(self::ENV_TOKEN)->getJson(self::REPORT)->assertStatus(503);

        $page->fillForm(['enabled' => true, 'token' => ''])->call('save')->assertHasNoFormErrors();
        $this->withToken(self::NEW_TOKEN)->getJson(self::REPORT)->assertOk();
    }

    public function test_generation_stays_in_the_form_until_saved_and_the_page_shows_endpoints(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/service-api-settings')->assertOk()
            ->assertSee('/api/v1/service/reports/daily?date=YYYY-MM-DD')
            ->assertSee('/api/v1/service/reports/monthly?month=YYYY-MM')
            ->assertSee('Europe/Bucharest');

        $page = Livewire::test(ServiceApiSettings::class)->callFormComponentAction('token', 'generateToken');
        $token = $page->get('data.token');
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $token);
        $this->assertNull(CompanySetting::first()->service_report_api_token);
        $this->withToken(self::ENV_TOKEN)->getJson(self::REPORT)->assertOk();

        $page->call('save')->assertHasNoFormErrors()->assertFormSet(['token' => '']);
        $this->withToken($token)->getJson(self::REPORT)->assertOk();
    }

    public function test_enabling_requires_a_valid_token_and_invalid_replacement_does_not_change_it(): void
    {
        $this->actingAs(User::factory()->create());
        config(['service_reporting.api_token' => '']);
        $page = Livewire::test(ServiceApiSettings::class)
            ->fillForm(['enabled' => true, 'token' => ''])->call('save')->assertHasFormErrors(['token']);

        $page->fillForm(['enabled' => true, 'token' => 'short'])->call('save')->assertHasFormErrors(['token']);
        $page->fillForm(['enabled' => true, 'token' => str_repeat('a ', 32)])->call('save')->assertHasFormErrors(['token']);
        $this->assertNull(CompanySetting::first()->service_report_api_token);
        $this->assertNull(CompanySetting::first()->service_report_api_enabled);
    }
}
