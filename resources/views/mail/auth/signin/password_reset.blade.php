<x-mail::message>
# Hello {{ $user->username }}

<x-mail::panel>
    Reset password code : <b> {{ $otp }} </b>
</x-mail::panel>


Thanks,<br>
{{ env("APP_NAME") }}
</x-mail::message>
