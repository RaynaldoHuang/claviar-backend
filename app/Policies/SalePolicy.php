<?php
namespace App\Policies;
use App\Models\User;
class SalePolicy { public function before(User $u): ?bool { return $u->hasRole('super-admin') ? true : null; } public function viewAny(User $u): bool { return $u->can('manage sales'); } public function view(User $u): bool { return $this->viewAny($u); } public function create(User $u): bool { return $this->viewAny($u); } public function update(User $u): bool { return $this->viewAny($u); } public function delete(User $u): bool { return false; } }
