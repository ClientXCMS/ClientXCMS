<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */


namespace App\DTO\Admin;

class MassActionDTO
{
    public string $action;

    public array $ids = [];

    public ?string $question = null;

    public ?string $response = null;

    public $callback;

    public string $translate;

    public function __construct(string $action, string $translate, callable $callback, ?string $question = null)
    {
        $this->action = $action;
        $this->callback = $callback;
        $this->question = $question;
        $this->translate = $translate;
    }

    public function execute(string $model)
    {
        $success = [];
        $errors = [];
        foreach ($this->ids as $id) {
            $model = $model::find($id);
            if ($model == null) {
                continue;
            }
            try {
                call_user_func_array($this->callback, [$model, $this->response]);
                $success[$model->id] = 'Success';
            } catch (\Exception $e) {
                $errors[$model->Id] = $e->getMessage();
            }
        }

        return [$success, $errors];
    }

    public function setResponse(array $ids, ?string $response = null): void
    {
        $this->ids = $ids;
        $this->response = $response;
    }
}
