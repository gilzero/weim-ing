<?php

namespace Drupal\ai_provider_aws_bedrock\Plugin\AiProvider;

use Aws\Bedrock\BedrockClient;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\AiProviderClientBase;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatInterface;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\OperationType\Embeddings\EmbeddingsInput;
use Drupal\ai\OperationType\Embeddings\EmbeddingsInterface;
use Drupal\ai\OperationType\Embeddings\EmbeddingsOutput;
use Drupal\ai\OperationType\TextToImage\TextToImageInput;
use Drupal\ai\OperationType\TextToImage\TextToImageInterface;
use Drupal\ai\OperationType\TextToImage\TextToImageOutput;
use Drupal\ai_provider_aws_bedrock\BedrockChatMessageIterator;
use Drupal\ai_provider_aws_bedrock\Models\Chat\Ai21Chat;
use Drupal\ai_provider_aws_bedrock\Models\Chat\AnthropicChat;
use Drupal\ai_provider_aws_bedrock\Models\Chat\CohereChat;
use Drupal\ai_provider_aws_bedrock\Models\Chat\MetaChat;
use Drupal\ai_provider_aws_bedrock\Models\Chat\MistralChat;
use Drupal\ai_provider_aws_bedrock\Models\Chat\TitanChat;
use Drupal\ai_provider_aws_bedrock\Models\Embeddings\CohereEmbeddings;
use Drupal\ai_provider_aws_bedrock\Models\Embeddings\TitanEmbeddings;
use Drupal\ai_provider_aws_bedrock\Models\TextToImage\StableDiffusion;
use Drupal\ai_provider_aws_bedrock\Models\TextToImage\TitanImage;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Plugin implementation of the 'bedrock' provider.
 */
#[AiProvider(
  id: 'bedrock',
  label: new TranslatableMarkup('AWS Bedrock'),
)]
class BedrockProvider extends AiProviderClientBase implements
  ContainerFactoryPluginInterface,
  ChatInterface,
  EmbeddingsInterface,
  TextToImageInterface {

  /**
   * The AWS Bedrock Runtime Client.
   *
   * @var \Aws\BedrockRuntime\BedrockRuntimeClient|null
   */
  protected $client;

  /**
   * The AWS Model Configuration client.
   *
   * @var \Aws\Bedrock\BedrockClient|null
   */
  protected $modelClient;

  /**
   * The AWS Bedrock factory.
   *
   * @var \Drupal\aws\AwsClientFactoryInterface
   */
  protected $clientFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Profile.
   *
   * @var string
   */
  protected string $profile = '';

  /**
   * Run moderation call, before a normal call.
   *
   * @var bool|null
   */
  protected bool|null $moderation = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->clientFactory = $container->get('aws.client_factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguredModels(string $operation_type = NULL, $capabilities = []): array {
    $this->loadClient();
    return $this->getModels($operation_type, $capabilities);
  }

  /**
   * {@inheritdoc}
   */
  public function isUsable(string $operation_type = NULL, $capabilities = []): bool {
    // If its not configured, it is not usable.
    if (!$this->getConfig()->get('profile')) {
      return FALSE;
    }
    // If its one of the bundles that AWS Bedrock supports its usable.
    if ($operation_type) {
      return in_array($operation_type, $this->getSupportedOperationTypes());
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedOperationTypes(): array {
    return [
      'chat',
      'embeddings',
      'moderation',
      'text_to_image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(): ImmutableConfig {
    return $this->configFactory->get('ai_provider_aws_bedrock.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getApiDefinition(): array {
    // Load the configuration.
    $definition = Yaml::parseFile($this->moduleHandler->getModule('ai_provider_aws_bedrock')->getPath() . '/definitions/api_defaults.yml');
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelSettings(string $model_id, array $generalConfig = []): array {
    // We need to hardcode stuff here for now, until the API/SDK gives back.
    switch ($model_id) {
      case strpos($model_id, 'ai21') === 0:
        Ai21Chat::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'cohere.command') === 0:
        CohereChat::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'anthropic') === 0:
        AnthropicChat::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'meta.llama') === 0:
        MetaChat::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'mistral.mistral') === 0:
        MistralChat::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'amazon.titan-text') === 0:
        TitanChat::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'amazon.titan-embed') === 0:
        TitanEmbeddings::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'cohere.embed') === 0:
        CohereEmbeddings::providerConfig($generalConfig, $model_id);
        break;

      case strpos($model_id, 'stability.stable-diffusion-xl') === 0:
        StableDiffusion::providerConfig($generalConfig);
        break;

      case strpos($model_id, 'amazon.titan-image-generator-v1') === 0:
        TitanImage::providerConfig($generalConfig);
        break;
    }
    return $generalConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthentication(mixed $authentication): void {
    // Set the new profile and reset the client.
    $this->profile = $authentication;
    $this->client = NULL;
    $this->modelClient = NULL;
  }

  /**
   * Enables moderation response, for all next coming responses.
   */
  public function enableModeration(): void {
    $this->moderation = TRUE;
  }

  /**
   * Disables moderation response, for all next coming responses.
   */
  public function disableModeration(): void {
    $this->moderation = FALSE;
  }

  /**
   * Gets the raw client.
   *
   * @param string $profile
   *   If the profile should be hot swapped.
   *
   * @return \Aws\BedrockRuntime\BedrockRuntimeClient
   *   The AWS Bedrock client.
   */
  public function getClient(string $profile = ''): BedrockRuntimeClient {
    // If the moderation is not set, we load it from the configuration.
    if (is_null($this->moderation)) {
      $this->moderation = $this->getConfig()->get('moderation');
    }
    if ($profile) {
      $this->setAuthentication($profile);
    }
    else {
      $this->setAuthentication($this->getDefaultProfile());
    }
    $this->loadClient();
    return $this->client;
  }

  /**
   * Get the raw model client.
   *
   * @param string $profile
   *   If the profile should be hot swapped.
   *
   * @return \Aws\Bedrock\BedrockClient
   *   The AWS Bedrock model client.
   */
  public function getModelClient(string $profile = ''): BedrockClient {
    if ($profile) {
      $this->setAuthentication($profile);
    }
    else {
      $this->setAuthentication($this->getDefaultProfile());
    }
    $this->loadClient();
    return $this->modelClient;
  }

  /**
   * Loads the AWS Bedrock Client with authentication if not initialized.
   */
  protected function loadClient(): void {
    if (!$this->client) {
      if (!$this->profile) {
        $this->setAuthentication($this->getDefaultProfile());
      }
      $this->modelClient = $this->clientFactory->setProfile($this->loadProfile())->getClient('bedrock');
      $this->client = $this->clientFactory->setProfile($this->loadProfile())->getClient('bedrockruntime');
    }
  }

  /**
   * Get the default profile.
   *
   * @return string
   *   The profile.
   */
  protected function getDefaultProfile(): string {
    return $this->getConfig()->get('profile');
  }

  /**
   * Load the profile.
   *
   * @return \Drupal\aws\Entity\ProfileInterface
   *   The AWS profile entity.
   */
  protected function loadProfile() {
    /** @var \Drupal\aws\Entity\ProfileInterface */
    return $this->entityTypeManager->getStorage('aws_profile')->load($this->profile);
  }

  /**
   * {@inheritdoc}
   */
  public function chat(array|string|ChatInput $input, string $model_id, array $tags = []): ChatOutput {
    $this->loadClient();
    // Normalize the input if needed.
    $chat_input = $input;
    $system_message = $this->chatSystemRole;
    if ($input instanceof ChatInput) {
      $chat_input = [];
      /** @var \Drupal\ai\OperationType\Chat\ChatMessage $message */
      foreach ($input->getMessages() as $key => $message) {
        $content = [
          [
            'text' => $message->getText(),
          ],
        ];
        if (count($message->getImages())) {
          foreach ($message->getImages() as $image) {
            $content[] = [
              'image' => [
                'format' => $image->getFileType() == 'jpg' ? 'jpeg' : $image->getFileType(),
                'source' => [
                  'bytes' => $image->getBinary(),
                ],
              ],
            ];
          }
        }
        $chat_input[] = [
          'role' => $message->getRole(),
          'content' => $content,
        ];
      }
    }

    // Normalize the configuration.
    $this->normalizeConfiguration('chat', $model_id);

    $payload = [
      'modelId' => $model_id,
      'messages' => $chat_input,
      'inferenceConfig' => $this->configuration,
    ];
    // Set system message.
    if ($system_message) {
      $payload['system'] = [['text' => $system_message]];
    }
    if ($this->streamed) {
      $response = $this->client->converseStream($payload);

      $message = new BedrockChatMessageIterator($response->get('stream'));
    }
    else {
      $response = $this->client->converse($payload);
      $message = new ChatMessage($response['output']['message']['role'], $response['output']['message']['content'][0]['text']);
    }

    return new ChatOutput($message, $response, $response['usage']);
  }

  /**
   * {@inheritdoc}
   */
  public function textToImage(string|TextToImageInput $input, string $model_id, array $tags = []): TextToImageOutput {
    $this->loadClient();
    // Normalize the input if needed.
    if ($input instanceof TextToImageInput) {
      $input = $input->getText();
    }
    // The send.
    switch ($model_id) {
      case strpos($model_id, 'stability.stable-diffusion-xl') === 0:
        $payload = StableDiffusion::formatInput($input, $this->configuration);
        break;

      case strpos($model_id, 'amazon.titan-image-generator-v1') === 0:
        $payload = TitanImage::formatInput($input, $this->configuration);
        break;
    }

    $response = $this->client->invokeModel([
      'modelId' => $model_id,
      'body' => json_encode($payload),
      'contentType' => 'application/json',
    ]);
    $body = json_decode($response['body'], TRUE);

    // The output.
    switch ($model_id) {
      case strpos($model_id, 'stability.stable-diffusion-xl') === 0:
        $images = StableDiffusion::formatOutput($body, $this->configuration);
        break;

      case strpos($model_id, 'amazon.titan-image-generator-v1') === 0:
        $images = TitanImage::formatOutput($body, $this->configuration);
        break;
    }
    return new TextToImageOutput($images, $response, []);
  }

  /**
   * {@inheritdoc}
   */
  public function embeddings(string|EmbeddingsInput $input, string $model_id, array $tags = []): EmbeddingsOutput {
    $this->loadClient();
    // Normalize the input if needed.
    if ($input instanceof EmbeddingsInput) {
      $text = $input->getPrompt();
      $image = $input->getImage();

      switch ($model_id) {
        case strpos($model_id, 'amazon.titan-embed') === 0:
          $payload = TitanEmbeddings::formatInput($text, $image, $this->configuration, $model_id);
          break;

        case strpos($model_id, 'cohere.embed') === 0:
          $payload = CohereEmbeddings::formatInput($text, $image, $this->configuration, $model_id);
          break;
      }
    }
    else {
      $payload = $input;
    }
    $response = $this->client->invokeModel([
      'modelId' => $model_id,
      'body' => json_encode($payload),
      'contentType' => 'application/json',
    ]);
    $body = json_decode($response['body'], TRUE);
    switch ($model_id) {
      case strpos($model_id, 'amazon.titan-embed') === 0:
        $embeddings = TitanEmbeddings::formatOutput($body, $this->configuration);
        break;

      case strpos($model_id, 'cohere.embed') === 0:
        $embeddings = CohereEmbeddings::formatOutput($body, $this->configuration);
        break;
    }

    return new EmbeddingsOutput($embeddings, $body, []);
  }

  /**
   * {@inheritdoc}
   */
  public function maxEmbeddingsInput($model_id = ''): int {
    return 1024;
  }

  /**
   * Obtains a list of models from AWS Bedrock and caches the result.
   *
   * This method does its best job to filter out deprecated or unused models.
   * The AWS Bedrock API endpoint does not have a way to filter those out yet.
   *
   * @param string $operation_type
   *   The bundle to filter models by.
   * @param array $capabilities
   *   The capabilities to filter models by.
   *
   * @return array
   *   A filtered list of public models.
   */
  public function getModels(string $operation_type, $capabilities = []): array {
    $models = [];

    $cache_key = 'bedrock_models_' . $operation_type . '_' . Crypt::hashBase64(Json::encode($capabilities));
    ;
    $cache_data = $this->cacheBackend->get($cache_key);

    if (!empty($cache_data)) {
      return $cache_data->data;
    }

    // Modality output.
    $output_modality = 'TEXT';
    switch ($operation_type) {
      case 'text_to_image':
        $output_modality = 'IMAGE';
        break;

      case 'embeddings':
        $output_modality = 'EMBEDDING';
        break;
    }

    $list = $this->modelClient->listFoundationModels([
      'byOutputModality' => $output_modality,
    ]);

    foreach ($list['modelSummaries'] as $model) {
      // Only active models.
      if ($model['modelLifecycle']['status'] !== 'ACTIVE') {
        continue;
      }
      // If we should only show on demand.
      if ($this->getConfig()->get('on_demand') && !in_array('ON_DEMAND', $model['inferenceTypesSupported'])) {
        continue;
      }
      // If the capabilities are not empty, we filter by them.
      if (count($capabilities)) {
        // Go through all the models.
        if (strpos($model['modelId'], 'ai21') === 0 && Ai21Chat::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if (strpos($model['modelId'], 'cohere.command') === 0 && CohereChat::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if (strpos($model['modelId'], 'anthropic') === 0 && AnthropicChat::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if (strpos($model['modelId'], 'meta.llama') === 0 && MetaChat::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if ((strpos($model['modelId'], 'mistral.mistral') === 0  || strpos($model['modelId'], 'mistral.mixtral') === 0) && MistralChat::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if ((strpos($model['modelId'], 'amazon.titan-text') === 0 || strpos($model['modelId'], 'amazon.titan-tg1') === 0) && TitanChat::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if (strpos($model['modelId'], 'amazon.titan-embed') === 0 && TitanEmbeddings::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if (strpos($model['modelId'], 'cohere.embed') === 0 && CohereEmbeddings::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if (strpos($model['modelId'], 'stability.stable-diffusion-xl') === 0 && StableDiffusion::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }

        if (strpos($model['modelId'], 'amazon.titan-image-generator-v1') === 0 && TitanImage::providerCapabilities($capabilities, $model['modelId']) === FALSE) {
          continue;
        }
      }
      $models[$model['modelId']] = $model['modelName'] . ' (' . $model['modelId'] . ')';
    }

    if (!empty($models)) {
      asort($models);
      $this->cacheBackend->set($cache_key, $models);
    }

    return $models;
  }

}
