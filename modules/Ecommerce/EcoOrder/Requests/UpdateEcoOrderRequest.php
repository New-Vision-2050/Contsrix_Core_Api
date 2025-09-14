<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoOrder\Commands\UpdateEcoOrderCommand;
use Modules\Ecommerce\EcoOrder\Handlers\UpdateEcoOrderHandler;

class UpdateEcoOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'eco_client_id' => 'nullable|uuid|exists:eco_clients,id',
            'is_guest' => 'boolean',
            'client_type' => 'nullable|string|max:10',
            'payment_status' => 'string|in:paid,unpaid,partially_paid',
            'order_status' => 'string|in:pending,confirmed,processing,out_for_delivery,delivered,returned,failed,canceled',
            'payment_method' => 'nullable|string|max:100',
            'transaction_ref' => 'nullable|string|max:30',
            'payment_by' => 'nullable|string|max:191',
            'payment_note' => 'nullable|string',
            'order_amount' => 'numeric|min:0',
            'paid_amount' => 'numeric|min:0',
            'bring_change_amount' => 'nullable|numeric|min:0',
            'bring_change_amount_currency' => 'nullable|string|max:255',
            'admin_commission' => 'numeric|min:0',
            'is_pause' => 'string|max:20',
            'cause' => 'nullable|string|max:191',
            'shipping_address' => 'nullable|string',
            'discount_amount' => 'numeric|min:0',
            'discount_type' => 'nullable|string|max:30',
            'coupon_code' => 'nullable|string|max:191',
            'coupon_discount_bearer' => 'string|max:191',
            'shipping_responsibility' => 'nullable|string|max:255',
            'shipping_method_id' => 'nullable|uuid',
            'shipping_cost' => 'numeric|min:0',
            'is_shipping_free' => 'boolean',
            'order_group_id' => 'string|max:191',
            'verification_code' => 'string|max:191',
            'verification_status' => 'boolean',
            'shipping_address_data' => 'nullable|array',
            'delivery_man_id' => 'nullable|uuid',
            'deliveryman_charge' => 'numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'order_note' => 'nullable|string',
            'billing_address' => 'nullable|uuid',
            'billing_address_data' => 'nullable|array',
            'order_type' => 'string|max:191',
            'extra_discount' => 'numeric|min:0',
            'extra_discount_type' => 'nullable|string|max:191',
            'refer_and_earn_discount' => 'numeric|min:0',
            'free_delivery_bearer' => 'nullable|string|max:255',
            'checked' => 'boolean',
            'shipping_type' => 'nullable|string|max:191',
            'delivery_type' => 'nullable|string|max:191',
            'delivery_service_name' => 'nullable|string|max:191',
            'third_party_delivery_tracking_id' => 'nullable|string|max:191',
        ];
    }

    public function createUpdateEcoOrderCommand(): UpdateEcoOrderCommand
    {
        return new UpdateEcoOrderCommand(
            id: Uuid::fromString($this->route('id')),
            eco_client_id: $this->get('eco_client_id'),
            is_guest: $this->boolean('is_guest'),
            client_type: $this->get('client_type'),
            payment_status: $this->get('payment_status'),
            order_status: $this->get('order_status'),
            payment_method: $this->get('payment_method'),
            transaction_ref: $this->get('transaction_ref'),
            payment_by: $this->get('payment_by'),
            payment_note: $this->get('payment_note'),
            order_amount: $this->get('order_amount') ? (float) $this->get('order_amount') : null,
            paid_amount: $this->get('paid_amount') ? (float) $this->get('paid_amount') : null,
            bring_change_amount: $this->get('bring_change_amount') ? (float) $this->get('bring_change_amount') : null,
            bring_change_amount_currency: $this->get('bring_change_amount_currency'),
            admin_commission: $this->get('admin_commission') ? (float) $this->get('admin_commission') : null,
            is_pause: $this->get('is_pause'),
            cause: $this->get('cause'),
            shipping_address: $this->get('shipping_address'),
            discount_amount: $this->get('discount_amount') ? (float) $this->get('discount_amount') : null,
            discount_type: $this->get('discount_type'),
            coupon_code: $this->get('coupon_code'),
            coupon_discount_bearer: $this->get('coupon_discount_bearer'),
            shipping_responsibility: $this->get('shipping_responsibility'),
            shipping_method_id: $this->get('shipping_method_id'),
            shipping_cost: $this->get('shipping_cost') ? (float) $this->get('shipping_cost') : null,
            is_shipping_free: $this->has('is_shipping_free') ? $this->boolean('is_shipping_free') : null,
            order_group_id: $this->get('order_group_id'),
            verification_code: $this->get('verification_code'),
            verification_status: $this->has('verification_status') ? $this->boolean('verification_status') : null,
            shipping_address_data: $this->get('shipping_address_data'),
            delivery_man_id: $this->get('delivery_man_id'),
            deliveryman_charge: $this->get('deliveryman_charge') ? (float) $this->get('deliveryman_charge') : null,
            expected_delivery_date: $this->get('expected_delivery_date'),
            order_note: $this->get('order_note'),
            billing_address: $this->get('billing_address'),
            billing_address_data: $this->get('billing_address_data'),
            order_type: $this->get('order_type'),
            extra_discount: $this->get('extra_discount') ? (float) $this->get('extra_discount') : null,
            extra_discount_type: $this->get('extra_discount_type'),
            refer_and_earn_discount: $this->get('refer_and_earn_discount') ? (float) $this->get('refer_and_earn_discount') : null,
            free_delivery_bearer: $this->get('free_delivery_bearer'),
            checked: $this->has('checked') ? $this->boolean('checked') : null,
            shipping_type: $this->get('shipping_type'),
            delivery_type: $this->get('delivery_type'),
            delivery_service_name: $this->get('delivery_service_name'),
            third_party_delivery_tracking_id: $this->get('third_party_delivery_tracking_id'),
        );
    }
}
