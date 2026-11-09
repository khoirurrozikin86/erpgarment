<?php

namespace App\Domain\Pos\Services;

use App\Domain\Pos\Actions\ClosePosSessionAction;
use App\Domain\Pos\Actions\OpenPosSessionAction;
use App\Models\PosSession;

class PosSessionService
{
    public function __construct(private OpenPosSessionAction $open, private ClosePosSessionAction $close) {}

    public function open(array $data, int $userId): PosSession { return ($this->open)($data, $userId); }
    public function close(PosSession $session, array $data, int $userId): PosSession { return ($this->close)($session, $data, $userId); }
}
