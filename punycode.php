<?php

/**
 * Punycode implementation algorithm from RFC 3492
 *
 * @link http://tools.ietf.org/html/rfc3492
 *
 */
class Punycode
{
	/**
	 * Параметры описанные в спецификации RFC 3492 раздел 5
	 *
	 */
	const BASE         = 36;
	const TMIN         = 1;
	const TMAX         = 26;
	const SKEW         = 38;
	const DAMP         = 700;
	const INITIAL_BIAS = 72;
	const INITIAL_N    = 128;

	const PREFIX       = 'xn--';
	const DELIMITER    = '-';

	/**
	 * Таблица символов
	 *
	 */
	protected static $charTable = array(
		'a', 'b', 'c', 'd', 'e', 'f',
		'g', 'h', 'i', 'j', 'k', 'l',
		'm', 'n', 'o', 'p', 'q', 'r',
		's', 't', 'u', 'v', 'w', 'x',
		'y', 'z', '0', '1', '2', '3',
		'4', '5', '6', '7', '8', '9',
	);

	/**
	 * Кодирует имя хоста по алгоритму punycode
	 *
	 * @param string $host
	 * @return string
	 */
	public function encode($host)
	{
		$host = trim($host);
		$host = mb_strtolower($host, "UTF-8");
		$parts = explode('.', $host);

		foreach ($parts as &$part) {
			$part = $this->punycodeEncode($part);
		}

		return implode('.', $parts);
	}

	/**
	 * Кодирует часть имени хоста
	 * По примеру из раздела 6.3 спецификации RFC 3492
	 *
	 * @param string $part
	 * @return string
	 */
	protected function punycodeEncode($part)
	{
		preg_match_all('#.#su', $part, $match);

		$output = '';
		$chars = $match[0];
		$ords = array();
		$n = self::INITIAL_N;
		$bias = self::INITIAL_BIAS;
		$delta = 0;
		$b = 0;
		$u = 0;

		foreach ($chars as $c) {
			$ord = $this->ord($c);
			if ($ord < 0x80) {
				$output .= $c;
				$b++;
			} else {
				$u++;
			}
			$ords[] = $ord;
		}

		$h = $b;

		if ($part === $output) {
			return $output;
		}

		if ($b > 0) {
			$output .= self::DELIMITER;
		}

		while ($u != 0) {
			$m = 0x7fffffff;
			foreach ($ords as $c) {
				if ($m > $c && $c >= $n) {
					$m = $c;
				}
			}

			$delta = $delta + ($m - $n) * ($h + 1);
			$n = $m;

			foreach ($ords as $c) {
				if ($c < $n) {
					$delta++;
					continue;
				}
				if ($c > $n) {
					continue;
				}

				$q = $delta;
				for ($k = self::BASE;; $k += self::BASE) {
					$t = $k - $bias;
					if ($t < self::TMIN) {
						$t = self::TMIN;
					} elseif ($t > self::TMAX) {
						$t = self::TMAX;
					}

					if ($q < $t) {
						break;
					}

					$index = $t + (($q - $t) % (self::BASE - $t));
					$output .= static::$charTable[$index];
					$q = ($q - $t) / (self::BASE - $t);
				}

				$output .= static::$charTable[$q];
				$bias = $this->adapt($delta, $h + 1, ($h === $b));
				$delta = 0;
				$h++;
				$u--;
			}

			$delta++;
			$n++;
		}

		return self::PREFIX . $output;
	}

	/**
	 * Декодирует имя хоста в кодировке punycode
	 *
	 * @param string $host
	 * @return string
	 */
	public function decode($host)
	{
		$host = trim($host);
		$host = mb_strtolower($host, "UTF-8");
		$parts = explode('.', $host);

		foreach ($parts as &$part) {
			$part = $this->punycodeDecode($part);
		}

		return implode('.', $parts);
	}

	/**
	 * Декодирует часть имени хоста в кодировке punycode
	 * По примеру из раздела 6.2 спецификации RFC 3492
	 *
	 * @param string $part
	 * @return string
	 */
	protected function punycodeDecode($part)
	{
		if (strpos($part, self::PREFIX) !== 0) {
			return $part;
		}

		$chars = preg_split('##u', $part, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($chars as $c) {
			$ord = $this->ord($c);
			if ($ord >= 0x30 && $ord <= 0x39) {
				continue;
			} else if ($ord >= 0x61 && $ord <= 0x7A) {
				continue;
			} else if ($ord == 0x2D) {
				continue;
			}
			return $part;
		}

		$part = substr($part, strlen(self::PREFIX));

		if ($part == "") {
			return $part;
		}

		$i = 0;
		$n = self::INITIAL_N;
		$bias = self::INITIAL_BIAS;
		$output = '';

		if (($pos = strrpos($part, self::DELIMITER)) === false) {
			$pos = 0;
		}

		if ($pos != 0) {
			$output = substr($part, 0, $pos++);
		}

		$outputLength = strlen($output);
		$inputLength = strlen($part);
		$charTable = array_flip(static::$charTable);

		while ($pos < $inputLength) {
			$oldi = $i;
			$w = 1;

			for ($k = self::BASE; ; $k += self::BASE) {
				$digit = $charTable[$part[$pos++]];
				$i = $i + ($digit * $w);
				$t = $k - $bias;
				if ($t < self::TMIN) {
					$t = self::TMIN;
				} elseif ($t > self::TMAX) {
					$t = self::TMAX;
				}
				if ($digit < $t) {
					break;
				}
				$w = $w * (self::BASE - $t);
			}

			$x = $outputLength = $outputLength + 1;
			$bias = $this->adapt($i - $oldi, $x, ($oldi === 0));
			$n = $n + floor($i / $x);
			$i = $i % $x;
			$output = mb_substr($output, 0, $i, "UTF-8") . $this->chr($n) . mb_substr($output, $i, $x - 1, "UTF-8");
			$i++;
		}

		return $output;
	}

	/**
	 * Функция адаптации смещения по примеру из раздела 6.1 спецификации RFC 3492
	 *
	 * @param float $delta
	 * @param integer $numPoints
	 * @param boolean $firstTime
	 *
	 * @return integer
	 */
	protected function adapt($delta, $numPoints, $firstTime)
	{
		$delta = ($firstTime) ? floor($delta / self::DAMP) : floor($delta / 2);
		$delta += floor($delta / $numPoints);

		$k = 0;
		while ($delta > ((self::BASE - self::TMIN) * self::TMAX) / 2) {
			$delta = floor($delta / (self::BASE - self::TMIN));
			$k += self::BASE;
		}

		return floor($k + ((self::BASE - self::TMIN + 1) * $delta) / ($delta + self::SKEW));
	}

	/**
	 * Получить код символа
	 *
	 * @param string $char
	 * @return integer
	 */
	protected function ord($chr)
	{
		$ord = ord($chr[0]);

		if($ord < 0x80) return $ord;
		if($ord < 0xC2) return false;
		if($ord < 0xE0) return ($ord & 0x1F) <<  6 | (ord($chr[1]) & 0x3F);
		if($ord < 0xF0) return ($ord & 0x0F) << 12 | (ord($chr[1]) & 0x3F) << 6  | (ord($chr[2]) & 0x3F);
		if($ord < 0xF5) return ($ord & 0x0F) << 18 | (ord($chr[1]) & 0x3F) << 12 | (ord($chr[2]) & 0x3F) << 6 | (ord($chr[3]) & 0x3F);

		return false;
	}

	/**
	 * Получить символ по его коду
	 *
	 * @param integer $code
	 * @return string
	 */
	protected function chr($ord)
	{
		if($ord < 0x80)		return chr($ord);
		if($ord < 0x800)	return chr(0xC0 | $ord >> 6)  . chr(0x80 | $ord & 0x3F);
		if($ord < 0x10000)	return chr(0xE0 | $ord >> 12) . chr(0x80 | $ord >> 6  & 0x3F) . chr(0x80 | $ord & 0x3F);
		if($ord < 0x110000)	return chr(0xF0 | $ord >> 18) . chr(0x80 | $ord >> 12 & 0x3F) . chr(0x80 | $ord >> 6 & 0x3F) . chr(0x80 | $ord & 0x3F);

		return false;
	}
}
