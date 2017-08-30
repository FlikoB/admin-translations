<?php namespace Brackets\AdminTranslations\Test\Feature;

use Brackets\AdminTranslations\Translation;
use Brackets\AdminTranslations\Test\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;

class TranslationsControllerTest extends TestCase
{

    /** @test */
    function authorized_user_can_see_translations_stored_in_database(){
        $this->authorizedToIndex();

        $this->createTranslation('*', 'admin', 'Default version', ['en' => '1 English version', 'sk' => '1 Slovak version']);
        $this->createTranslation('*', 'admin', 'some.key', ['en' => '2 English version', 'sk' => '2 Slovak version']);

        $this->get('/admin/translation')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertSee('some.key')
            ->assertSee('1 English version')
////            ->assertDontSee('1 Slovak version') // it is there, but it's only in JS source object, not visible on page, but we're gonna skip this assertion
            ->assertViewHas('locales', collect(['en', 'sk']))
            ;

        $this->assertCount(3, Translation::all());
    }

    ///** @test */
    function authorized_user_can_search_for_translations(){
        $this->authorizedToIndex();

        $this->createTranslation('*', 'admin', 'Default version', ['en' => '1English version', 'sk' => '1Slovak version']);
        $this->createTranslation('*', 'admin', 'some.key', ['en' => '2English version', 'sk' => '2Slovak version']);

        $this->get('/admin/translation?search=1Slovak')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertDontSee('some.key')
        ;
    }

    /** @test */
    function authorized_user_can_filter_by_group(){ $this->disableExceptionHandling();
        $this->authorizedToIndex();

        $this->createTranslation('*', 'admin', 'Default version', ['en' => '1 English version', 'sk' => '1 Slovak version']);
        $this->createTranslation('*', 'frontend', 'some.key', ['en' => '2 English version', 'sk' => '2 Slovak version']);

        $this->get('/admin/translation?group=admin')
            ->assertStatus(200)
            ->assertSee('Default version')
            ->assertDontSee('some.key')
        ;
    }

    /** @test */
    function not_authorized_user_cannot_see_or_update_anything(){
        $this->get('/admin/translation')
            ->assertStatus(403)
        ;

        $this->post('/admin/translation/1')
            ->assertStatus(403)
        ;
    }


    /** @test */
    function authorized_user_can_update_a_translation(){
        $this->authorizedToUpdate();

        $line = $this->createTranslation('*', 'admin', 'Default version', ['en' => '1 English version', 'sk' => '1 Slovak version']);

        $this->post('/admin/translation/'.$line->id, [
            'text' => [
                'sk'=> '1 Slovak changed version'
            ]
        ], [
            'X-Requested-With' => 'XMLHttpRequest'
        ])
            ->assertStatus(200)
            ->assertJson([])
        ;

        $this->assertEquals('1 Slovak changed version', $line->fresh()->text['sk']);
        $this->assertArrayNotHasKey('en', $line->fresh()->text);
    }

    protected function authorizedToIndex() {
        $this->authorizedTo('index');
    }

    protected function authorizedToUpdate() {
        $this->authorizedTo('edit');
    }

    private function authorizedTo($action) {
        $this->actingAs(new User);
        Gate::define('admin.translation.'.$action, function() { return true; });
    }

}
