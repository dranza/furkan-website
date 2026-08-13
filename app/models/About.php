<?php
declare(strict_types=1);

final class About {
  public static function getText(): string {
    return Settings::get('about_text', 'Merhaba! Panelden hakkımda içeriğini ekleyebilirsiniz.') ?? '';
  }
}
