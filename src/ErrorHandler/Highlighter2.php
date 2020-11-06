<?php

declare(strict_types=1);

namespace Chiron\ErrorHandler;

final class Highlighter2
{
    /** @var StyleInterface */
    private $r = null;

    /**
     * @param StyleInterface $renderer
     */
    public function __construct()//StyleInterface $renderer)
    {
        $this->r = new ConsoleStyle(); //$renderer;
    }

    /**
     * Highlight PHP source and return N lines around target line.
     *
     * @param string $source
     * @param int    $line
     * @param int    $around
     * @return string
     */
    public function highlightLines(string $source, int $line, int $around = 4): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $this->highlight($source)));

        $result = '';
        foreach ($lines as $number => $code) {
            $human = $number + 1;
            if (!empty($around) && ($human < $line - $around || $human >= $line + $around + 1)) {
                //Not included in a range
                continue;
            }

            $result .= $this->r->line($human, mb_convert_encoding($code, 'utf-8'), $human === $line);
        }

        return $result;
    }

    /**
     * Returns highlighted PHP source.
     *
     * @param string $source
     * @return string
     */
    public function highlight(string $source): string
    {
        $result = '';
        $previous = [];
        foreach ($this->getTokens($source) as $token) {
            $result .= $this->r->token($token, $previous);
            $previous = $token;
        }

        return $result;
    }

    /**
     * Get all tokens from PHP source normalized to always include line number.
     *
     * @param string $source
     * @return array
     */
    private function getTokens(string $source): array
    {
        $tokens = [];
        $line = 0;

        foreach (token_get_all($source) as $token) {
            if (isset($token[2])) {
                $line = $token[2];
            }

            if (!is_array($token)) {
                $token = [$token, $token, $line];
            }

            $tokens[] = $token;
        }

        return $tokens;
    }
}
