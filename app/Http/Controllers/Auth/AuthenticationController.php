<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SignInRequest;
use App\Http\Requests\Auth\SignUpRequest;
use App\Mail\Auth\PasswordResetMail;
use App\Mail\Auth\SignUpVerificationMail;
use App\Models\ResetPassword;
use App\Models\User;
use App\Traits\HttpResponses;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthenticationController extends Controller
{
    use HttpResponses;

    public function login (SignInRequest $request): JsonResponse
    {
        $request->validated($request->all());
        if (!Auth::attempt($request->only(["email", "password"]))) {
            return $this->error("", "Credentials did not match", 401);
        }

        $user = User::query()->where('email', $request->email)->first();

        if ($user->email_verified_at != null && $user->email_verified_at < now()) {
            return $this->success([
                "user" => $user,
                "token" => $user->createToken('API TOKEN OF '.$user->username)
            ]);
        }

        return $this->error("", "Please verify your email account", 401);

    }

    public function register(SignUpRequest $request): JsonResponse
    {

        $request->validated($request->all());
        $otp = rand(10000, 99999);

         $user = User::query()->create([
            "username"                => $request->username,
            "email"                   => $request->email,
            "phone"                   => $request->phone,
            "password"                => Hash::make($request->password),
             "otp"                    => $otp,
             "otp_created_at"         => now(),
        ]);

        $this->sendEmailVerificationSignUp($user, $otp);

        return $this->success([
            "user" => $user,
            "token" => $user->createToken('API TOKEN OF '.$user->username)
        ]);
    }

    public function logout(): JsonResponse
    {
        return $this->success("", "", 204);
    }

    public function verifyEmail(Request $request)
    {

        $user = User::query()->where('email', $request->email)->first();

        $otpProvided = $request->input('otp');
        if (($otpProvided == $user->otp) && $this->isOtpValid($user->otp_created_at)) {
            $user->update([
                "otp_email_verification" => true,
                "email_verified_at"      => now(),
            ]);
            return $this->success();
        }
        return $this->error("","Invalide verification code", 401);
    }

    public function forgotPassword(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->error("", "User not found", 404);
        }

        $token = rand(100000, 999999);

        ResetPassword::query()->updateOrCreate([
                'user_id' => $user->id,
                'otp' => $token, 'created_at' => now()
            ]);

        $this->sendPasswordResetOtp($user, $token);
        return $this->success("", "Password reset OTP sent successfully");
    }

    public function resetPassword(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error("", "User not found", 404);
        }

        $passwordReset = ResetPassword::where('user_id', $user->id)
            ->where('otp', $request->otp)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if (!$passwordReset) {
            return $this->error("", "Invalid or expired OTP", 401);
        }

        $user->update(['password' => bcrypt($request->password)]);
        $passwordReset->delete();

        return $this->success("", "Password reset successfully");
    }



    //Helper functions
    private function sendEmailVerificationSignUp(User $user, int $otp): void
    {
        Mail::to($user)->queue(new SignUpVerificationMail($user, $otp));
    }
    private function sendPasswordResetOtp(User $user, int $otp): void
    {
        Mail::to($user)->queue(new PasswordResetMail($user, $otp));
    }


    private function isOtpValid($otpCreatedAt)
    {
        $otpCreatedAt = Carbon::parse($otpCreatedAt);
        $validityPeriod = now()->subMinutes(10);
        return $otpCreatedAt->gt($validityPeriod);
    }

}
