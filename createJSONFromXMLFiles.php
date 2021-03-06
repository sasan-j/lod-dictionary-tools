<?php

$folder = 'lod-dictionary-mirror/XML';

$lemmas = [];
foreach (new DirectoryIterator($folder) as $i => $fileInfo) {
    if ($fileInfo->isDot()) continue;

    if ($i > 100) continue; // Only the first 100 (for tests). Remember to remove this.

    $filename = $fileInfo->getPathname();

    $contents = file_get_contents($filename);

    // Screw the namespace
    $contents = str_replace('<lod:', '<', $contents);
    $contents = str_replace('</lod:', '</', $contents);
    $contents = str_replace(' lod:', ' ', $contents);

    $xml = simplexml_load_string($contents);

    $data = [];

    $id = $xml->ITEM[0]->META['ID'];

    $data['id'] = (string)$id;

    $lemma = $xml->ITEM[0]->ARTICLE[0]->{'ITEM-ADRESSE'}[0];

    $data['lemma'] = (string)$lemma;

    $pos            = '';
    $microstructure = $xml->ITEM[0]->ARTICLE[0]->MICROSTRUCTURE[0];
    if ($microstructure) {
        $msTypeAdj      = $microstructure->{'MS-TYPE-ADJ'}[0];
        $msTypeAdv      = $microstructure->{'MS-TYPE-ADV'}[0];
        $msTypeArt      = $microstructure->{'MS-TYPE-ART'}[0];
        $msTypeConj     = $microstructure->{'MS-TYPE-CONJ'}[0];
        $msTypeEComp    = $microstructure->{'MS-TYPE-ELEM-COMP'}[0];
        $msTypePart     = $microstructure->{'MS-TYPE-PART'}[0];
        $msTypePrep     = $microstructure->{'MS-TYPE-PREP'}[0];
        $msTypePrepPArt = $microstructure->{'MS-TYPE-PREP-plus-ART'}[0];
        $msTypePron     = $microstructure->{'MS-TYPE-PRON'}[0];
        $msTypePronAdv  = $microstructure->{'MS-TYPE-PRONADV'}[0];
        $msTypeSubst    = $microstructure->{'MS-TYPE-SUBST'}[0];
        $msTypeVrb      = $microstructure->{'MS-TYPE-VRB'}[0];

        if ($msTypeAdj) {
            $pos = 'ADJ';
        } elseif ($msTypeAdv) {
            $pos = 'ADV';
        } elseif ($msTypeArt) {
            $pos = 'ART';
        } elseif ($msTypeConj) {
            $pos = 'CONJ';
        } elseif ($msTypeEComp) {
            $pos = 'ECOMP';
        } elseif ($msTypePart) {
            $pos = 'PART';
        } elseif ($msTypePrep) {
            $pos = 'PREP';
        } elseif ($msTypePrepPArt) {
            $pos = 'PREPPART';
        } elseif ($msTypePron) {
            $pos = 'PRON';
        } elseif ($msTypePronAdv) {
            $pos = 'PRONADV';
        } elseif ($msTypeSubst) {
            $pos = 'SUBST';
        } elseif ($msTypeVrb) {
            $pos = 'VRB';
        } else {
            echo 'UNKNOWN POS: ' . $lemma . ' ' . $msTypeSubst . "\n";
            $pos = '??';
        }

        $data['pos'] = $pos;

        $ms = $microstructure->{'MS-TYPE-' . $pos}[0];

        if ($ms) {
//            $catGramSubst = $msTypeSubst->{'CAT-GRAM-SUBST'}[0];
//            $catGramVrb   = $msTypeSubst->{'CAT-GRAM-VRB'}[0];

            $genre          = $ms->GENRE;
            $type           = $ms->{'TYPE-' . $pos}[0];
            $pluriel        = $ms->PLURIEL[0];
            $traitementLing = $ms->{'TRAITEMENT-LING-' . $pos}[0];


            if ($genre) {
                $data['gen'] = (string)$genre[0]['GEN'];
            }

            if ($type) {
            }

            if ($pluriel) {
                $tjNombrable = $pluriel->{'TJ-NOMBRABLE'}[0];
                if ($tjNombrable) {
                    $formePluriel = $tjNombrable->{'FORME-PLURIEL'};

                    if ($formePluriel) {
                        $plurals = [];
                        foreach ($formePluriel as $plural) {
                            $plurals[] = (string)$plural;
                        }
                        $data['plurals'] = $plurals;
                    }
                }
            }

            if ($traitementLing) {
                $uniteTrad = $traitementLing->{'UNITE-TRAD'}[0];
                if ($uniteTrad) {
                    $pasDeTradSubordonnante = $uniteTrad->{'PAS-DE-TRAD-SUBORDONNANTE'}[0];
                    if ($pasDeTradSubordonnante) {
                        $unitesDeSens = $pasDeTradSubordonnante->{'UNITE-DE-SENS'};
                        if ($unitesDeSens) {
                            $data['translations'] = [];

                            $i = 0;
                            foreach ($unitesDeSens as $uniteDeSens) {

                                $domSpec      = $unitesDeSens->{'DOM-SPEC'};
                                $marqueUsage  = $unitesDeSens->{'MARQUE-USAGE'};
                                $equivTradAll = $unitesDeSens->{'EQUIV-TRAD-ALL'};
                                $equivTradFr  = $unitesDeSens->{'EQUIV-TRAD-FR'};
                                $equivTradEn  = $unitesDeSens->{'EQUIV-TRAD-EN'};
                                $equivTradPo  = $unitesDeSens->{'EQUIV-TRAD-PO'};

                                $data['translations'][$i] = [
                                    'de' => [],
                                    'fr' => [],
                                    'en' => [],
                                    'pt' => [],
                                ];
                                foreach ($equivTradAll->children() as $translation) {
                                    if (!strpos($translation->getName(), 'ABSENTE')) {
                                        $data['translations'][$i]['de'][] = (string)$translation;
                                    }
                                }

                                foreach ($equivTradFr->children() as $translation) {
                                    if (!strpos($translation->getName(), 'ABSENTE')) {
                                        $data['translations'][$i]['fr'][] = (string)$translation;
                                    }
                                }

                                if ($equivTradEn) {
                                    foreach ($equivTradEn->children() as $translation) {
                                        if (!strpos($translation->getName(), 'ABSENTE')) {
                                            $data['translations'][$i]['en'][] = (string)$translation;
                                        }
                                    }
                                }

                                if ($equivTradPo) {
                                    foreach ($equivTradPo->children() as $translation) {
                                        if (!strpos($translation->getName(), 'ABSENTE')) {
                                            $data['translations'][$i]['pt'][] = (string)$translation;
                                        }
                                    }
                                }
                                $i++;
                            }
                        }
                    }
                }
            }
        }
    }

    if ($pos === 'VRB') {
        $flx = $xml->ITEM[0]->{'FLX-VRB'}[0];
        if ($flx) {
            $data['flx'] = [];
            foreach ($flx->children() as $verbform) {
                if ($verbform->children()) {
                    foreach ($verbform->children() as $subform) {
                        foreach ($subform->children() as $subsubform) {
                            $data['flx'][strtolower($verbform->getName())][strtolower($subform->getName())][strtolower($subsubform->getName())] = (string)$subsubform;
                        }
                    }
                } else {
                    $data['flx'][strtolower($verbform->getName())] = (string)$verbform;
                }
            }
        }
    }

    $lemmas[] = $data;
}

file_put_contents('lod.json', json_encode($lemmas, JSON_PRETTY_PRINT));
