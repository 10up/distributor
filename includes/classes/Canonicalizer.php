<?php

namespace Syndicate;

/**
 * Canonicalzers let us setup a syndicated post - canonical link, proper content, title, etc.
 */
abstract class Canonicalizer {

	public abstract function setup();
}
