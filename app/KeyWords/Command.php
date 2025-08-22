<?php

namespace App\KeyWords;

use LINE\Webhook\Model\MessageEvent;

interface Command
{
    /**
     * 檢查是否可以處理此訊息
     */
    public function canHandle(string $message): bool;

    /**
     * 處理命令並回傳回覆訊息
     */
    public function handle(MessageEvent $event): array;

    /**
     * 取得命令描述
     */
    public function getDescription(): string;

    /**
     * 向後相容的方法
     * @deprecated 使用 handle() 方法替代
     */
    public function replyCommand(): array;
}
