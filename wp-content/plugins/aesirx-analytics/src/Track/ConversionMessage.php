<?php

namespace AesirxAnalytics\Track;

class ConversionMessage extends AbstractMessage {

    private array $items = [];
    private string $flowUuid;
    private ?string $eventName = null;
    private ?string $eventType = null;
    private string $extension;

    private array $order = [
        'order_id' => null,
        'shipping' => 0,
        'discount' => 0,
        'tax'      => 0,
    ];
    private string $url;

    public function __construct(string $flowUuid, string $extension, string $url)
    {
        $this->flowUuid = $flowUuid;
        $this->extension = $extension;
        $this->url = $url;
    }

    public function addItem( string $sku, string $name, float $price, int $quantity ): self {
        $this->items[] = [
            'sku'      => $sku,
            'name'     => $name,
            'price'    => $price,
            'quantity' => $quantity,
        ];

        return $this;
    }

    public function setOrderDetails(string $orderId, float $shipping = null, float $discount = null, float $tax = null): self
    {
        $this->order['order_id'] = $orderId;
        $this->order['shipping'] = $shipping;
        $this->order['discount'] = $discount;
        $this->order['tax'] = $tax;

        return $this;
    }

    public function setEventNameType(?string $name = null, ?string $type = null): self
    {
        $this->eventName = $name;
        $this->eventType = $type;

        return $this;
    }

    protected function multiply(float $val): int
    {
        return round($val * 100);
    }

    public function __serialize(): array {

        $subtotal = 0;
        $items = [];

        foreach ($this->items as $item)
        {
            $subtotal += $item['price'] * $item['quantity'];
            $item['price'] = $this->multiply($item['price']);
            $items[] = $item;
        }

        $data = [
            'items'      => $items,
            'flow_uuid'  => $this->flowUuid,
            'extension'  => $this->extension,
            'order_id'   => $this->order['order_id'],
            'url'        => $this->url,
            'revenue'    => [
                'total'    => $this->multiply($subtotal + $this->order['shipping'] + $this->order['tax'] - $this->order['discount']),
                'subtotal' => $this->multiply($subtotal),
                'shipping' => $this->multiply($this->order['shipping']),
                'discount' => $this->multiply($this->order['discount']),
                'tax'      => $this->multiply($this->order['tax']),
            ],
        ];

        if ($this->eventType)
        {
            $data['event_type'] = $this->eventType;
        }

        if ($this->eventName)
        {
            $data['event_name'] = $this->eventName;
        }

        return json_encode($data);
    }

    public function asCliCommand(): array
    {
        $subtotal = 0;
        $items = [];

        foreach ($this->items as $item)
        {
            $subtotal += $item['price'] * $item['quantity'];
            $item['price'] = $this->multiply($item['price']);
            $items[] = '--items';
            $items[] = json_encode($item);
        }

        if ($this->order['order_id'])
        {
            $items[] = '--order-id';
            $items[] = $this->order['order_id'];
        }

        if ($this->eventName)
        {
            $items[] = '--event-name';
            $items[] = $this->eventName;
        }

        if ($this->eventType)
        {
            $items[] = '--event-type';
            $items[] = $this->eventType;
        }

        return array_merge([
            'conversion', 'replace', 'v1',
            '--flow-uuid', $this->flowUuid,
            '--extension', $this->extension,
            '--url', $this->url,
            '--revenue-total', $this->multiply($subtotal + $this->order['shipping'] + $this->order['tax'] - $this->order['discount']),
            '--revenue-subtotal', $this->multiply($subtotal),
            '--revenue-shipping', $this->multiply($this->order['shipping']),
            '--revenue-discount', $this->multiply($this->order['discount']),
            '--revenue-tax', $this->multiply($this->order['tax']),
        ], $items);
    }
}