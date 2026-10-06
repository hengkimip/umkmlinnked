<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail ke setiap Super Admin aktif saat akun Super Admin / Admin OPD baru dibuat di Kelola Akses.
 * Kata sandi tidak pernah dicantumkan.
 */
class AkunAdminDibuat extends Notification
{
    use Queueable;

    public function __construct(public User $akun, public User $pembuat) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $peran = $this->akun->roleLabel();

        return (new MailMessage)
            ->subject("[UMKMLinked] Akun {$peran} baru: {$this->akun->name}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Akun **{$peran}** baru telah dibuat di UMKMLinked:")
            ->line("- Nama: {$this->akun->name}")
            ->line("- E-mail: {$this->akun->email}")
            ->line('- Instansi: ' . ($this->akun->instansi() ?? '-'))
            ->line('- Jabatan: ' . ($this->akun->jabatan ?: '-'))
            ->line("- Dibuat oleh: {$this->pembuat->name} ({$this->pembuat->email})")
            ->line('- Waktu: ' . now()->translatedFormat('d F Y H:i') . ' ' . config('app.timezone'))
            ->action('Buka Kelola Akses', route('superadmin.akses.index'))
            ->line('Bila pembuatan akun ini tidak Anda kenali, segera nonaktifkan akun tersebut di Kelola Akses.')
            ->salutation('Salam, ' . config('umkm.instansi_super_admin'));
    }
}
