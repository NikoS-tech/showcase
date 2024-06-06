<?php

namespace App\Services\Notifications\Mailer;

use App\Helpers\Mailing;
use App\Http\Models\Users;
use Closure;
use Illuminate\Support\Facades\Validator;

class Mailer
{
    protected array $emails = [];

    public function __construct($emails = [])
    {
        if (is_string($emails)) {
            $emails = explode('|', $emails);
        }

        $emails = is_array($emails) ? $emails : [$emails];
        $this->setEmails($emails);
    }

    public function withAdminEmails(): self
    {
        $this->setEmails($this->getDefaultEmails());
        return $this;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getDefaultEmails(): array
    {
        return Mailing::adminEmails();
    }

    public function setEmail(?string $email): void
    {
        if ($this->validateEmail($email) && !in_array($email, $this->emails)) {
            $this->emails[] = $email;
        }
    }

    public function setEmails(array $emails): void
    {
        foreach ($emails as $email) {
            $this->setEmail($email);
        }
    }

    protected function validateEmail(?string $email): bool
    {
        $data = compact('email');
        $validator = Validator::make($data, [
            'email' => 'required|email|max:255',
        ]);
        return !$validator->fails();
    }

    public function when(bool $isTrue, Closure $func): self
    {
        if ($isTrue) {
            call_user_func($func, $this);
        }

        return $this;
    }
}
