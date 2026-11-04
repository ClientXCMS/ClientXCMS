<?php

namespace App\Core\Domain;

enum DomainTransferStatus: string
{
    case Pending = 'pending';

    case Completed = 'completed';

    case Failed = 'failed';
}
