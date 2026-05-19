const API_URL = process.env.NEXT_PUBLIC_API_URL;

const request = async <T>(path: string, options: TRequestOptions = {}): Promise<T> => {
	const { method = "GET", body, token } = options;

	const headers: Record<string, string> = {
		Accept: "application/ld+json",
	};

	if (body) {
		headers["Content-Type"] = method === "PATCH" ? "application/merge-patch+json" : "application/ld+json";
	}

	if (token) {
		headers["Authorization"] = `Bearer ${token}`;
	}

	const response = await fetch(`${API_URL}${path}`, {
		method,
		headers,
		body: body ? JSON.stringify(body) : undefined,
	});

	const data = await response.json().catch(() => ({}));

	if (!response.ok) {
		const message = data.message ?? data.detail ?? data.error ?? "An unexpected error occurred.";
		throw new Error(message);
	}

	return data as T;
};

export const api = {
	get: <T>(path: string, token?: string) => request<T>(path, { token }),
	post: <T>(path: string, body: unknown, token?: string) => request<T>(path, { method: "POST", body, token }),
	put: <T>(path: string, body: unknown, token?: string) => request<T>(path, { method: "PUT", body, token }),
	patch: <T>(path: string, body: unknown, token?: string) => request<T>(path, { method: "PATCH", body, token }),
	delete: <T>(path: string, token?: string) => request<T>(path, { method: "DELETE", token }),
};

type TRequestOptions = {
	method?: string;
	body?: unknown;
	token?: string;
};

export default api;
