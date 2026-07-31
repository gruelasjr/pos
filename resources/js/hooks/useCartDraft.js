import { useCallback, useEffect, useState } from "react";

const DB_NAME = "pos-faro";
const STORE_NAME = "cart-drafts";
const VERSION = 1;

const openDatabase = () =>
    new Promise((resolve, reject) => {
        if (!("indexedDB" in window)) return reject(new Error("IndexedDB no disponible"));
        const request = indexedDB.open(DB_NAME, VERSION);
        request.onupgradeneeded = () => {
            if (!request.result.objectStoreNames.contains(STORE_NAME)) {
                request.result.createObjectStore(STORE_NAME);
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

export const useCartDraft = (cartId) => {
    const [restoredDraft, setRestoredDraft] = useState(null);

    useEffect(() => {
        if (!cartId) return;
        openDatabase().then((db) => {
            const request = db.transaction(STORE_NAME).objectStore(STORE_NAME).get(cartId);
            request.onsuccess = () => setRestoredDraft(request.result ?? null);
        }).catch(() => {});
    }, [cartId]);

    const saveDraft = useCallback((draft) => {
        if (!cartId) return;
        openDatabase().then((db) => {
            db.transaction(STORE_NAME, "readwrite").objectStore(STORE_NAME).put({
                version: VERSION,
                savedAt: new Date().toISOString(),
                ...draft,
            }, cartId);
        }).catch(() => {});
    }, [cartId]);

    const clearDraft = useCallback(() => {
        if (!cartId) return;
        openDatabase().then((db) => {
            db.transaction(STORE_NAME, "readwrite").objectStore(STORE_NAME).delete(cartId);
        }).catch(() => {});
    }, [cartId]);

    return { restoredDraft, saveDraft, clearDraft };
};
