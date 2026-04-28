<?php

namespace App\Services;

use App\Exceptions\BloggerApiException;
use App\Models\User;
use Google\Service\Exception as Google_Service_Exception;
use Google_Client;
use Google_Service_Blogger;
use Illuminate\Support\Facades\Http;

class BloggerService
{
    public function __construct(private User $user) {}

    private function getClient(): Google_Service_Blogger
    {
        if ($this->user->google_token_expires_at && $this->user->google_token_expires_at->isPast()) {
            $this->refreshToken();
            $this->user->refresh();
        }

        $client = new Google_Client();
        $client->setAccessToken(decrypt($this->user->google_access_token));

        return new Google_Service_Blogger($client);
    }

    private function refreshToken(): void
    {
        $refreshToken = decrypt($this->user->google_refresh_token);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new BloggerApiException('Failed to refresh Google access token.');
        }

        $data = $response->json();

        $this->user->update([
            'google_access_token' => encrypt($data['access_token']),
            'google_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);
    }

    public function getBlogs(): array
    {
        try {
            $service = $this->getClient();
            $blogList = $service->blogs->listByUser('self');
            $blogs = [];

            foreach ($blogList->getItems() ?? [] as $blog) {
                $blogs[] = [
                    'id' => $blog->getId(),
                    'name' => $blog->getName(),
                    'url' => $blog->getUrl(),
                ];
            }

            return $blogs;
        } catch (Google_Service_Exception $e) {
            throw BloggerApiException::fromGoogleException($e);
        }
    }

    public function getPosts(string $blogId): array
    {
        try {
            $service = $this->getClient();
            $posts = [];
            $pageToken = null;

            do {
                $params = ['maxResults' => 500, 'status' => ['LIVE', 'DRAFT', 'SCHEDULED']];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $response = $service->posts->listPosts($blogId, $params);

                foreach ($response->getItems() ?? [] as $post) {
                    $posts[] = [
                        'id' => $post->getId(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'url' => $post->getUrl(),
                        'labels' => $post->getLabels() ?? [],
                        'status' => $post->getStatus(),
                        'published' => $post->getPublished(),
                    ];
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $posts;
        } catch (Google_Service_Exception $e) {
            throw BloggerApiException::fromGoogleException($e);
        }
    }

    public function getPost(string $blogId, string $postId): array
    {
        try {
            $service = $this->getClient();
            $post = $service->posts->get($blogId, $postId);

            return [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'url' => $post->getUrl(),
                'labels' => $post->getLabels() ?? [],
                'status' => $post->getStatus(),
                'published' => $post->getPublished(),
            ];
        } catch (Google_Service_Exception $e) {
            throw BloggerApiException::fromGoogleException($e);
        }
    }

    public function updatePost(string $blogId, string $postId, array $data): void
    {
        try {
            $service = $this->getClient();
            $post = new \Google_Service_Blogger_Post();
            $post->setTitle($data['title']);
            $post->setContent($data['content']);
            $post->setLabels($data['labels'] ?? []);
            $service->posts->patch($blogId, $postId, $post);
        } catch (Google_Service_Exception $e) {
            throw BloggerApiException::fromGoogleException($e);
        }
    }

    public function publishPost(string $blogId, string $postId): void
    {
        try {
            $service = $this->getClient();
            $service->posts->publish($blogId, $postId);
        } catch (Google_Service_Exception $e) {
            throw BloggerApiException::fromGoogleException($e);
        }
    }

    public function revertToDraft(string $blogId, string $postId): void
    {
        try {
            $service = $this->getClient();
            $service->posts->revert($blogId, $postId);
        } catch (Google_Service_Exception $e) {
            throw BloggerApiException::fromGoogleException($e);
        }
    }

    public function deletePost(string $blogId, string $postId): void
    {
        try {
            $service = $this->getClient();
            $service->posts->delete($blogId, $postId);
        } catch (Google_Service_Exception $e) {
            throw BloggerApiException::fromGoogleException($e);
        }
    }
}
