<?php

namespace App\Enums;

enum TenantStatus: string {
    case OPEN = 'buka';
    case CLOSED = 'tutup';
    case BREAK = 'istirahat';
    case BUSY = 'sibuk';
    case PERMANENTLY_CLOSED = 'tutup-permanent';
}