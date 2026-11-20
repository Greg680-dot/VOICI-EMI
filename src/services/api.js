const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '/api').replace(/\/+$/, '');

export async function apiRequest(path, options = {}) {
  const normalizedPath = path.replace(/^\/+/, '');
  const body = options.body;
  const hasBody = body !== undefined;
  const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
  const response = await fetch(`${apiBaseUrl}/${normalizedPath}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(hasBody && !isFormData ? { 'Content-Type': 'application/json' } : {}),
      ...options.headers,
    },
    body: hasBody && !isFormData && typeof body !== 'string'
      ? JSON.stringify(body)
      : body,
  });

  if (!response.ok) {
    const details = await response.text();
    throw new Error(
      `Erreur API Laravel (${response.status} ${response.statusText})${details ? ` : ${details}` : ''}`,
    );
  }

  if (response.status === 204) return null;

  const contentType = response.headers.get('content-type') || '';
  return contentType.includes('application/json')
    ? response.json()
    : response.text();
}
