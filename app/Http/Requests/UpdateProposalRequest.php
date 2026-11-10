<?php

namespace App\Http\Requests;

class UpdateProposalRequest extends StoreProposalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('proposal'));
    }
}
