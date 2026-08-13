import axios, { AxiosInstance } from 'axios';
import type { paths } from './types';

type HttpMethod = 'get' | 'put' | 'post' | 'patch' | 'delete' | 'head' | 'options';

type PathKeys = keyof paths;

type ValidMethod<P extends PathKeys> = {
    [K in keyof paths[P]]: K extends HttpMethod ? (paths[P][K] extends never ? never : K) : never;
}[keyof paths[P]];

type Operation<P extends PathKeys, M extends ValidMethod<P>> = paths[P][M];

type PathParams<P extends PathKeys, M extends ValidMethod<P>> = Operation<P, M> extends {
    parameters: { path: infer R };
}
    ? R
    : undefined;

type QueryParams<P extends PathKeys, M extends ValidMethod<P>> = Operation<P, M> extends {
    parameters: { query: infer R };
}
    ? R
    : undefined;

type RequestBody<P extends PathKeys, M extends ValidMethod<P>> = Operation<P, M> extends {
    requestBody: { content: { 'application/json': infer R } };
}
    ? R
    : undefined;

type SuccessStatus<R> = Extract<keyof R, 200 | 201 | 202 | 204>;

type ResponseBody<P extends PathKeys, M extends ValidMethod<P>> = Operation<P, M> extends {
    responses: infer R;
}
    ? SuccessStatus<R> extends never
        ? void
        : SuccessStatus<R> extends infer S
        ? S extends keyof R
            ? R[S] extends { content: { 'application/json': infer C } }
                ? C
                : void
            : void
        : void
    : void;

type RequestConfig<P extends PathKeys, M extends ValidMethod<P>> = {
    path: P;
    method: M;
    pathParams?: PathParams<P, M>;
    query?: QueryParams<P, M>;
    body?: RequestBody<P, M>;
    headers?: Record<string, string>;
};

type ApiClientOptions = {
    baseURL?: string;
    token?: string;
    axiosInstance?: AxiosInstance;
};

const DEFAULT_BASE_URL = '/api/v1';

const compilePath = (template: string, params?: Record<string, unknown>): string => {
    if (!params) {
        return template;
    }

    return template.replace(/\{([^}]+)}/g, (_, key: string) => {
        if (!(key in params)) {
            throw new Error(`Missing path parameter: ${key}`);
        }

        const value = params[key];
        return encodeURIComponent(String(value ?? ''));
    });
};

const createIdempotencyKey = (): string => {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `idem-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};

export const createApiClient = (options: ApiClientOptions = {}) => {
    const http =
        options.axiosInstance ??
        axios.create({
            baseURL: options.baseURL ?? DEFAULT_BASE_URL,
        });

    if (typeof document !== 'undefined') {
        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            http.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        }
    }

    if (options.token) {
        http.interceptors.request.use((config) => {
            config.headers = config.headers ?? {};
            config.headers.Authorization = `Bearer ${options.token}`;
            return config;
        });
    }

    const request = async <P extends PathKeys, M extends ValidMethod<P>>(
        config: RequestConfig<P, M>,
    ): Promise<ResponseBody<P, M>> => {
        const url = compilePath(config.path as string, config.pathParams as Record<string, unknown> | undefined);
        const response = await http.request({
            method: config.method,
            url,
            params: config.query,
            data: config.body,
            headers: config.headers,
        });

        return response.data as ResponseBody<P, M>;
    };

    return {
        http,
        request,
        warehouses: {
            list: (query?: QueryParams<'/warehouses', 'get'>) =>
                request({ path: '/warehouses', method: 'get', query }),
            create: (body: RequestBody<'/warehouses', 'post'>) =>
                request({ path: '/warehouses', method: 'post', body }),
            update: (id: string, body: RequestBody<'/warehouses/{warehouseId}', 'patch'>) =>
                request({
                    path: '/warehouses/{warehouseId}',
                    method: 'patch',
                    pathParams: { warehouseId: id },
                    body,
                }),
        },
        productTypes: {
            list: (query?: QueryParams<'/product-types', 'get'>) =>
                request({ path: '/product-types', method: 'get', query }),
            create: (body: RequestBody<'/product-types', 'post'>) =>
                request({ path: '/product-types', method: 'post', body }),
            update: (id: string, body: RequestBody<'/product-types/{productTypeId}', 'patch'>) =>
                request({
                    path: '/product-types/{productTypeId}',
                    method: 'patch',
                    pathParams: { productTypeId: id },
                    body,
                }),
        },
        productTags: {
            list: (query?: Record<string, unknown>) => http.get('/product-tags', { params: query }).then(r => r.data),
            create: (body: Record<string, unknown>) => http.post('/product-tags', body).then(r => r.data),
            update: (id: string, body: Record<string, unknown>) => http.patch(`/product-tags/${id}`, body).then(r => r.data),
            remove: (id: string) => http.delete(`/product-tags/${id}`).then(r => r.data),
        },
        metadataDefinitions: {
            list: (query?: Record<string, unknown>) => http.get('/product-metadata-definitions', { params: query }).then(r => r.data),
            create: (body: Record<string, unknown>) => http.post('/product-metadata-definitions', body).then(r => r.data),
            update: (id: string, body: Record<string, unknown>) => http.patch(`/product-metadata-definitions/${id}`, body).then(r => r.data),
            remove: (id: string) => http.delete(`/product-metadata-definitions/${id}`).then(r => r.data),
        },
        metadataCodedValues: {
            create: (definitionId: string, body: Record<string, unknown>) => http.post(`/product-metadata-definitions/${definitionId}/coded-values`, body).then(r => r.data),
            update: (id: string, body: Record<string, unknown>) => http.patch(`/product-metadata-coded-values/${id}`, body).then(r => r.data),
        },
        products: {
            list: (query?: QueryParams<'/products', 'get'>) =>
                request({ path: '/products', method: 'get', query }),
            create: (body: RequestBody<'/products', 'post'>) =>
                request({ path: '/products', method: 'post', body }),
            show: (id: string) =>
                request({
                    path: '/products/{product}',
                    method: 'get',
                    pathParams: { product: id },
                }),
            update: (id: string, body: RequestBody<'/products/{product}', 'patch'>) =>
                request({
                    path: '/products/{product}',
                    method: 'patch',
                    pathParams: { product: id },
                    body,
                }),
            uploadPhoto: (id: string, photo: File) => {
                const form = new FormData();
                form.append('photo', photo);
                return http.post(`/products/${id}/photo`, form).then(r => r.data);
            },
        },
        inventory: {
            list: (query?: QueryParams<'/inventory', 'get'>) =>
                request({ path: '/inventory', method: 'get', query }),
            adjust: (body: RequestBody<'/inventory/adjust', 'patch'>) =>
                request({ path: '/inventory/adjust', method: 'patch', body }),
            update: (id: string, body: Record<string, unknown>) =>
                http.patch(`/inventory/${id}`, body).then(r => r.data),
        },
        skuRanges: {
            list: (query?: Record<string, unknown>) => http.get('/sku-ranges', { params: query }).then(r => r.data),
            match: (sku: string) => http.get('/sku-ranges/match', { params: { sku } }).then(r => r.data),
            create: (body: Record<string, unknown>) => http.post('/sku-ranges', body).then(r => r.data),
            update: (id: string, body: Record<string, unknown>) => http.patch(`/sku-ranges/${id}`, body).then(r => r.data),
        },
        skus: {
            reserve: (body: RequestBody<'/skus/reserve', 'post'>) =>
                request({ path: '/skus/reserve', method: 'post', body }),
        },
        carts: {
            list: (query?: QueryParams<'/carts', 'get'>) => request({ path: '/carts', method: 'get', query }),
            create: (body: RequestBody<'/carts', 'post'>) => request({ path: '/carts', method: 'post', body }),
            update: (id: string, body: RequestBody<'/carts/{cart}', 'patch'>) =>
                request({ path: '/carts/{cart}', method: 'patch', pathParams: { cart: id }, body }),
            addItem: (id: string, body: RequestBody<'/carts/{cart}/items', 'post'>) =>
                request({ path: '/carts/{cart}/items', method: 'post', pathParams: { cart: id }, body }),
            updateItem: (id: string, itemId: string, body: RequestBody<'/carts/{cart}/items/{itemId}', 'patch'>) =>
                request({
                    path: '/carts/{cart}/items/{itemId}',
                    method: 'patch',
                    pathParams: { cart: id, itemId },
                    body,
                }),
            removeItem: (id: string, itemId: string) =>
                request({
                    path: '/carts/{cart}/items/{itemId}',
                    method: 'delete',
                    pathParams: { cart: id, itemId },
                }),
            clear: (id: string) => http.delete(`/carts/${id}/items`).then(r => r.data),
            checkout: (id: string, body: RequestBody<'/carts/{cart}/checkout', 'post'>) =>
                request({
                    path: '/carts/{cart}/checkout',
                    method: 'post',
                    pathParams: { cart: id },
                    body,
                    headers: { 'X-Idempotency-Key': createIdempotencyKey() },
                }),
        },
        customers: {
            list: (query?: QueryParams<'/customers', 'get'>) =>
                request({ path: '/customers', method: 'get', query }),
            create: (body: RequestBody<'/customers', 'post'>) =>
                request({ path: '/customers', method: 'post', body }),
            update: (id: string, body: RequestBody<'/customers/{customer}', 'patch'>) =>
                request({
                    path: '/customers/{customer}',
                    method: 'patch',
                    pathParams: { customer: id },
                    body,
                }),
            register: (body: RequestBody<'/customers/register', 'post'>) =>
                request({ path: '/customers/register', method: 'post', body }),
        },
        sales: {
            list: (query?: QueryParams<'/sales', 'get'>) => request({ path: '/sales', method: 'get', query }),
            show: (id: string) =>
                request({ path: '/sales/{sale}', method: 'get', pathParams: { sale: id } }),
            sendReceipt: (id: string, body: RequestBody<'/sales/{sale}/receipt', 'post'>) =>
                request({
                    path: '/sales/{sale}/receipt',
                    method: 'post',
                    pathParams: { sale: id },
                    body,
                }),
            print: (id: string) => http.post(`/sales/${id}/print`).then(r => r.data),
        },
        reports: {
            daily: (query?: QueryParams<'/reports/daily', 'get'>) =>
                request({ path: '/reports/daily', method: 'get', query }),
            weekly: (query?: QueryParams<'/reports/weekly', 'get'>) =>
                request({ path: '/reports/weekly', method: 'get', query }),
            monthly: (query?: QueryParams<'/reports/monthly', 'get'>) =>
                request({ path: '/reports/monthly', method: 'get', query }),
            bySeller: (query?: QueryParams<'/reports/by-seller', 'get'>) =>
                request({ path: '/reports/by-seller', method: 'get', query }),
            overview: (query?: Record<string, unknown>) => http.get('/reports/overview', { params: query }).then(r => r.data),
            bestSellers: (query?: Record<string, unknown>) => http.get('/reports/best-sellers', { params: query }).then(r => r.data),
            exportUrl: (report: 'overview' | 'best-sellers', format: 'csv' | 'pdf', query: Record<string, unknown> = {}) => {
                const params = new URLSearchParams();
                params.set('report', report);
                params.set('format', format);
                Object.entries(query).forEach(([key, value]) => {
                    if (Array.isArray(value)) value.forEach(item => params.append(`${key}[]`, String(item)));
                    else if (value !== undefined && value !== null && value !== '') params.set(key, String(value));
                });
                return `/api/v1/reports/export?${params.toString()}`;
            },
        },
    };
};

export type ApiClient = ReturnType<typeof createApiClient>;
