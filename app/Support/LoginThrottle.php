<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Bloqueo anti-fuerza-bruta escalado, en dos dimensiones:
 *  - Por cuenta+IP: 5 fallos → penalización creciente (1, 5, 15, 60 min).
 *  - Por IP global: 25 fallos en 15 min contra cualquier cuenta.
 *
 * Portado de SmileIntelli (clinica_dental) app/Support/LoginThrottle.php.
 */
class LoginThrottle
{
    const MAX_ACCT = 5;

    const MAX_IP = 25;

    const IP_WINDOW = 900; // 15 min

    /** Minutos de castigo por nivel. */
    const NIVELES = [1, 5, 15, 60];

    private function acctKey(string $email, string $ip): string
    {
        return 'login:acct:'.sha1(mb_strtolower($email).'|'.$ip);
    }

    private function ipKey(string $ip): string
    {
        return 'login:ip:'.$ip;
    }

    /**
     * Segundos restantes de bloqueo, o null si puede intentar.
     */
    public function bloqueado(string $email, string $ip): ?int
    {
        if ((int) Cache::get($this->ipKey($ip), 0) >= self::MAX_IP) {
            return self::IP_WINDOW;
        }

        $until = Cache::get($this->acctKey($email, $ip).':until');

        if ($until && ($secs = now()->diffInSeconds($until, false)) > 0) {
            return (int) $secs;
        }

        return null;
    }

    public function fallo(string $email, string $ip): void
    {
        Cache::add($this->ipKey($ip), 0, self::IP_WINDOW);
        Cache::increment($this->ipKey($ip));

        $key = $this->acctKey($email, $ip);
        $fails = (int) Cache::get($key.':fails', 0) + 1;
        Cache::put($key.':fails', $fails, 3600);

        if ($fails >= self::MAX_ACCT) {
            $nivel = min((int) Cache::get($key.':nivel', 0), count(self::NIVELES) - 1);
            $mins = self::NIVELES[$nivel];
            Cache::put($key.':until', now()->addMinutes($mins), $mins * 60);
            Cache::put($key.':nivel', $nivel + 1, 86400);
            Cache::forget($key.':fails');
        }
    }

    public function exito(string $email, string $ip): void
    {
        $key = $this->acctKey($email, $ip);
        Cache::forget($key.':fails');
        Cache::forget($key.':until');
        Cache::forget($key.':nivel');
    }
}
