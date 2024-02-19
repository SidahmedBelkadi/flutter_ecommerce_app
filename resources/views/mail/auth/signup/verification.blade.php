<x-mail::message>
# Hello {{ $user->username }}

<x-mail::panel>
    Please confirm your email address, here is the code : <b> {{ $otp }} </b>
</x-mail::panel>


Thanks,<br>
{{ env("APP_NAME") }}
</x-mail::message>

