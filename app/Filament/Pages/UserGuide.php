<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UserGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $title = 'Ghid de Utilizare';
    protected static ?string $navigationLabel = 'Ghid de Utilizare';
    protected static ?int $navigationSort = 999999;

    protected static string $view = 'filament.pages.user-guide';
}
