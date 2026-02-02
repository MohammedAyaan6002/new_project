import os
import logging
from typing import List, Dict, Any

from flask import Flask, request, jsonify
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import spacy

app = Flask(__name__)
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Configurable from env (no hardcoded threshold)
DEFAULT_MIN_SCORE = float(os.getenv("AI_MATCH_THRESHOLD", "0.35"))
DEFAULT_TOP_N = int(os.getenv("AI_MATCH_TOP_N", "5"))
MAX_ITEMS = int(os.getenv("AI_MATCH_MAX_ITEMS", "500"))
MAX_QUERY_LENGTH = int(os.getenv("AI_MATCH_MAX_QUERY_LENGTH", "2000"))

MODEL_NAME = os.getenv("SPACY_MODEL", "en_core_web_sm")
try:
    nlp = spacy.load(MODEL_NAME)
except OSError:
    nlp = spacy.blank("en")


def normalize_text(text: str) -> str:
    if not text or not text.strip():
        return ""
    doc = nlp(text.lower().strip()[:5000])
    tokens = [token.lemma_ for token in doc if token.is_alpha and not token.is_stop]
    return " ".join(tokens) if tokens else text.lower().strip()[:500]


def build_corpus_weighted(query: str, items: List[Dict]) -> List[str]:
    """Build corpus with weighted fields: name and description count more than location."""
    q_norm = normalize_text(query)
    corpus = [q_norm]
    for item in items:
        name = (item.get("item_name") or "").strip()
        desc = (item.get("description") or "").strip()
        loc = (item.get("location") or "").strip()
        # Weight name 2x, description 2x, location 1x by repeating in combined text
        combined = " ".join([name, name, desc, desc, loc])
        corpus.append(normalize_text(combined))
    return corpus


@app.post("/match")
def match_items():
    try:
        payload = request.get_json(force=True, silent=True) or {}
    except Exception as e:
        logger.warning("Invalid JSON body: %s", e)
        return jsonify({"matches": [], "message": "Invalid JSON body"}), 400

    query = (payload.get("query") or "").strip()
    items = payload.get("items") or []
    min_score = float(payload.get("min_score", DEFAULT_MIN_SCORE))
    top_n = int(payload.get("top_n", DEFAULT_TOP_N))

    if not query:
        return jsonify({"matches": [], "message": "Query is required"}), 400
    if len(query) > MAX_QUERY_LENGTH:
        return jsonify({"matches": [], "message": "Query too long"}), 400
    if not items or not isinstance(items, list):
        return jsonify({"matches": [], "message": "Items list is required"}), 400
    if len(items) > MAX_ITEMS:
        items = items[:MAX_ITEMS]
    if not (0 <= min_score <= 1):
        min_score = DEFAULT_MIN_SCORE
    if top_n < 1 or top_n > 50:
        top_n = DEFAULT_TOP_N

    try:
        corpus = build_corpus_weighted(query, items)
    except Exception as e:
        logger.exception("Corpus build failed: %s", e)
        return jsonify({"matches": [], "message": "Processing failed"}), 500

    try:
        vectorizer = TfidfVectorizer()
        tfidf_matrix = vectorizer.fit_transform(corpus)
    except Exception as e:
        logger.exception("Vectorizer failed: %s", e)
        return jsonify({"matches": [], "message": "Matching failed"}), 500

    query_vec = tfidf_matrix[0]
    items_vec = tfidf_matrix[1:]
    similarities = cosine_similarity(query_vec, items_vec).flatten()

    ranked: List[Dict[str, Any]] = []
    for idx, score in enumerate(similarities):
        item = items[idx] if idx < len(items) else {}
        ranked.append({
            "item_id": item.get("id"),
            "item_name": item.get("item_name"),
            "description": item.get("description"),
            "location": item.get("location"),
            "item_type": item.get("item_type"),
            "score": float(score),
            "query_label": query[:60],
        })
    ranked.sort(key=lambda r: r["score"], reverse=True)
    top_matches = [m for m in ranked if m["score"] >= min_score][:top_n]

    return jsonify({"matches": top_matches, "count": len(top_matches)})


@app.get("/health")
def health():
    return jsonify({"status": "ok"})


@app.errorhandler(400)
def bad_request(e):
    return jsonify({"error": "Bad request", "message": str(e)}), 400


@app.errorhandler(404)
def not_found(e):
    return jsonify({"error": "Not found", "message": str(e)}), 404


@app.errorhandler(405)
def method_not_allowed(e):
    return jsonify({"error": "Method not allowed"}), 405


@app.errorhandler(500)
def internal_error(e):
    logger.exception("Internal error: %s", e)
    return jsonify({"error": "Internal server error", "message": "An unexpected error occurred"}), 500


if __name__ == "__main__":
    port = int(os.getenv("PORT", 5001))
    app.run(host="0.0.0.0", port=port)
