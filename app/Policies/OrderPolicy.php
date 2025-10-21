<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all orders
        if ($user->isSuperAdmin()) {
            return true;
        }

        // All tenant users can view orders (scoped by tenant automatically)
        return $user->tenant_id !== null;
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Super admins can view all orders
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can only view orders from their own tenant
        return $user->tenant_id === $order->tenant_id;
    }

    /**
     * Determine if the user can retry the order.
     */
    public function retry(User $user, Order $order): bool
    {
        // Super admins can retry all orders
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can retry orders from their own tenant
        return $user->tenant_id === $order->tenant_id && $user->isTenantAdmin($order->tenant_id);
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Super admins can delete all orders
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can delete orders from their own tenant
        return $user->tenant_id === $order->tenant_id && $user->isTenantAdmin($order->tenant_id);
    }

    /**
     * Determine if the user can export orders.
     */
    public function export(User $user): bool
    {
        // Super admins can export all orders
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Tenant admins can export their own orders
        return $user->tenant_id !== null && $user->isTenantAdmin($user->tenant_id);
    }
}
