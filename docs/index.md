# Filament Live Preview

## Introduction

A Filament plugin to add a preview screen to your pages using websockets. The screen will render your website with the data from Filament, without saving it.
This is heavily based on [Filament Peek](https://github.com/pboivin/filament-peek).

## Installation

You can install the package via composer:

```bash
composer require wotz/filament-live-preview
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-live-preview-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-live-preview-views"
```

## Usage

### Install Laravel Reverb

Follow the Laravel Reverb installation instructions [here](https://laravel.com/docs/12.x/reverb)

#### Fix Livewire and Reverb

In `bootstrap/app.php`, add the following lines:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'livewire/*',
        ]);
    })
```

Since we reload Livewire via websockets, we need to exclude the Livewire routes from CSRF protection. Else you will get a 419 HTTP status code when Livewire tries to make requests.

### Setup Filament Live Preview

#### Update Filament resource

```php

use Filament\Schemas\Components\Section;public static function form(Schema $schema): Schema
{
    return $schema
        ->columns(3)
        ->components([
            Section::make()
                ->schema([
                    // Your fields here
                ])
                ->columnSpan(function (Component $livewire) {
                    return ['lg' => $livewire->isPreviewing() ? 1 : 3];
                })
                ->extraAttributes([
                    'data-live-preview-form' => '',
                ]),

            View::make('filament-live-preview::components.live-preview-frame')
                ->hidden(fn (Component $livewire) => ! $livewire->isPreviewing())
                ->columnSpan(['lg' => 2]),
        ]);
}
```

#### Update EditPage / CreatePage

```php
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;use Wotz\FilamentLivePreview\Filament\Traits\HasLivePreviewComponent;

class EditPage extends EditRecord
{
    use HasLivePreviewComponent;
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->action(fn () => $this->toggleIsPreviewing()),
            $this->getSaveFormAction()->submit(null)->action('save'),
            DeleteAction::make(),
        ];
    }
    
    protected function getPreviewModalView(): ?string
    {
        // This corresponds to resources/views/posts/preview.blade.php
        return 'page.show';
    }

    protected function getPreviewModalDataRecordKey(): ?string
    {
        return 'page';
    }

    protected function mutatePreviewModalData(array $data): array
    {
        $data['titleForLayout'] = $data['page']->title;

        return $data;
    }
}
```

See the [Peek](https://github.com/pboivin/filament-peek/blob/3.x/docs/page-previews.md#adding-extra-data-to-previews) docs for more details on how to customize the preview data.

#### Install the plugin in your panel

```php
use Filament\Support\Enums\Width;
use Pboivin\FilamentPeek\FilamentPeekPlugin;
use Wotz\FilamentLivePreview\LivePreviewPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            // Your other plugins...
            FilamentPeekPlugin::make() // Disable styles and scripts for peek, since we override them in our package
                ->disablePluginStyles()
                ->disablePluginScripts(),
            LivePreviewPlugin::make(),
        ])
        ->maxContentWidth(Width::Full) // This is optional, but makes that your panel uses the full width of the screen
        ;
}
```

#### Fix rendering of live preview component

Since we use a [full page Livewire component](https://livewire.laravel.com/docs/components#full-page-components) to render the preview, and you use a Blade component for layout, you can make some changes to 

In our projects we have this `AppLayout` component:

```php
<?php

namespace App\View\Components;

use App\Models\Page;
use App\Models\StaticPage;
use Closure;
use Codedor\Seo\Facades\SeoBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppLayout extends Component
{
    public function __construct(
        public string $titleForLayout,
    ) {
        $this->renderDiv = request()->routeIs('live-preview-frame') || request()->is('livewire/update');
    }

    public function render(): View|Closure|string
    {
        if ($this->renderDiv) {
            return <<<'blade'
        <div>
            {{ $slot }}
        </div>
    blade;
        }

        return view('layouts.app');
    }
}
```

This make sure that the view defined in `getPreviewModalView` is wrapped in a `<div>` instead of the full layout, when rendering the live preview component.

#### Add broadcasting route

In `routes/channels.php`, add the following line:

```php
Broadcast::channel('live-preview', function () {});
```
