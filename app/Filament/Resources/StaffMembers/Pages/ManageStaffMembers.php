<?php

namespace App\Filament\Resources\StaffMembers\Pages;

use App\Filament\Resources\StaffMembers\StaffMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffMembers extends ManageRecords
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
