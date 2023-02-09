<?php

namespace Distributor;

/**
 * This class use to register script.
 *
 * This class handles:
 *   - Script dependencies.
 *     This uses asset information to set script dependencies,
 *     and version generated by @wordpress/dependency-extraction-webpack-plugin package.
 *   - Script localization.
 *     It also handles script translation registration.
 *
 * @unreleased x.x.x
 * @package    distributor
 */
class EnqueueScript {
	/**
	 * Script ID.
	 *
	 * @unreleased x.x.x
	 */
	private string $scriptId;

	/**
	 * Script path relative to plugin root directory.
	 *
	 * @unreleased x.x.x
	 */
	private string $relativeScriptPath;

	/**
	 * Script path absolute to plugin root directory.
	 *
	 * @unreleased x.x.x
	 */
	private string $absoluteScriptPath;

	/**
	 * Script dependencies.
	 *
	 * @unreleased x.x.x
	 */
	private array $scriptDependencies = [];

	/**
	 * Script version.
	 *
	 * @unreleased x.x.x
	 */
	private string $version = '';

	/**
	 * Flag to decide whether load script in footer.
	 *
	 * @unreleased x.x.x
	 */
	private bool $loadScriptInFooter = false;

	/**
	 * Flag to decide whether register script translation.
	 *
	 * @unreleased x.x.x
	 */
	private bool $registerTranslations = false;

	/**
	 * Script localization parameter name.
	 *
	 * @unreleased x.x.x
	 */
	private string $localizeScriptParamName;

	/**
	 * Script localization parameter data.
	 *
	 * @unreleased x.x.x
	 */
	private array $localizeScriptParamData;

	/**
	 * Plugin root directory path.
	 *
	 * @unreleased x.x.x
	 */
	private string $pluginDirPath;

	/**
	 * Plugin root directory URL.
	 *
	 * @unreleased x.x.x
	 */
	private string $pluginDirUrl;

	/**
	 * Plugin text domain.
	 *
	 * @unreleased x.x.x
	 */
	private string $textDomain;

	/**
	 * EnqueueScript constructor.
	 *
	 * @unreleased x.x.x
	 *
	 * @param string $scriptId Script ID.
	 * @param string $scriptName
	 */
	public function __construct( string $scriptId, string $scriptName ) {
		$this->pluginDirPath      = DT_PLUGIN_PATH;
		$this->pluginDirUrl       = trailingslashit( plugin_dir_url( $this->pluginDirPath ) );
		$this->textDomain         = 'distributor';
		$this->scriptId           = $scriptId;
		$this->relativeScriptPath = 'dist/js/' . $scriptName . '.js';
		$this->absoluteScriptPath = $this->pluginDirPath . $this->relativeScriptPath;
	}

	/**
	 * @unreleased x.x.x
	 *
	 * @param string $version
	 *
	 * @return $this
	 */
	public function version( $version ): EnqueueScript {
		$this->version = $version;

		return $this;
	}

	/**
	 * @unreleased x.x.x
	 * @return $this
	 */
	public function loadInFooter(): EnqueueScript {
		$this->loadScriptInFooter = true;

		return $this;
	}

	/**
	 * @unreleased x.x.x
	 *
	 * @param array $scriptDependencies
	 *
	 * @return $this
	 */
	public function dependencies( array $scriptDependencies ): EnqueueScript {
		$this->scriptDependencies = $scriptDependencies;

		return $this;
	}

	/**
	 * @unreleased x.x.x
	 * @return $this
	 */
	public function register(): EnqueueScript {
		$scriptUrl   = $this->pluginDirUrl . $this->relativeScriptPath;
		$scriptAsset = $this->getAssetFileData();

		wp_register_script(
			$this->scriptId,
			$scriptUrl,
			$scriptAsset['dependencies'],
			$scriptAsset['version'],
			$this->loadScriptInFooter
		);

		if ( $this->registerTranslations ) {
			wp_set_script_translations(
				$this->scriptId,
				$this->textDomain,
				$this->pluginDirPath . 'languages'
			);
		}

		if ( $this->localizeScriptParamData ) {
			wp_localize_script(
				$this->scriptId,
				$this->localizeScriptParamName,
				$this->localizeScriptParamData
			);
		}

		return $this;
	}

	/**
	 * This function should be called after enqueue or register function.
	 *
	 * @unreleased x.x.x
	 * @return $this
	 */
	public function registerTranslations(): EnqueueScript {
		$this->registerTranslations = true;

		return $this;
	}

	/**
	 * This function should be called after enqueue or register function.
	 *
	 * @param string $jsVariableName Name of the variable to be used in JS.
	 * @param array  $data           Data to be localized.
	 *
	 * @return $this
	 */
	public function registerLocalizeData( string $jsVariableName, array $data ): EnqueueScript {
		$this->localizeScriptParamName = $jsVariableName;
		$this->localizeScriptParamData = $data;

		return $this;
	}

	/**
	 * @unreleased x.x.x
	 *
	 * @return $this Class object.
	 */
	public function enqueue(): EnqueueScript {
		if ( ! wp_script_is( $this->scriptId, 'registered' ) ) {
			$this->register();
		}
		wp_enqueue_script( $this->scriptId );

		return $this;
	}

	/**
	 * @unreleased x.x.x
	 *
	 * @return string
	 */
	public function getScriptId(): string {
		return $this->scriptId;
	}

	/**
	 * @unreleased x.x.x
	 *
	 * @return array
	 */
	public function getAssetFileData(): array {
		$scriptAssetPath = trailingslashit( dirname( $this->absoluteScriptPath ) )
			. basename( $this->absoluteScriptPath, '.js' )
			. '.asset.php';

		$scriptAsset = file_exists( $scriptAssetPath )
			? require( $scriptAssetPath )
			: [ 'dependencies' => [], 'version' => $this->version ?: filemtime( $this->absoluteScriptPath ) ];

		if ( $this->scriptDependencies ) {
			$scriptAsset['dependencies'] = array_merge( $this->scriptDependencies, $scriptAsset['dependencies'] );
		}

		return $scriptAsset;
	}
}