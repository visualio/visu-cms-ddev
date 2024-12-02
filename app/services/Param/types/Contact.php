<?php

namespace App\Services\Params;

class Contact
{
    public readonly string $email;
    public readonly string $phone;
    public readonly string $title;
    public readonly string $name;
    public readonly string $street;
    public readonly string $city;
    public readonly string $postCode;
    public readonly string $country;
    public readonly string $crn;
    public readonly string $vatId;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->phone = $data['phone'];
        $this->title = $data['title'];
        $this->name = $data['name'];
        $this->street = $data['street'];
        $this->city = $data['city'];
        $this->postCode = $data['postCode'];
        $this->country = $data['country'];
        $this->crn = $data['crn'];
        $this->vatId = $data['vatId'];
    }
}