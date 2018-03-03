<?php
// Increase php memory limits
ini_set('memory_limit', '256M');
$folder = '../lod-dictionary-mirror/XML';

$debug = FALSE;

$lemmas = [];
foreach (new DirectoryIterator($folder) as $fc => $fileInfo) {
    if ($fileInfo->isDot()) continue;

    //if ($fc > 150) continue; // Only the first 100 (for tests). Remember to remove this.

    $filename = $fileInfo->getPathname();

    if ($debug) {
        $filename = "../lod-dictionary-mirror/XML/GUTT2.xml";
    }

    $contents = file_get_contents($filename);

    // Screw the namespace
    $contents = str_replace('<lod:', '<', $contents);
    $contents = str_replace('</lod:', '</', $contents);
    $contents = str_replace(' lod:', ' ', $contents);

    $unite_trads_col = array("PAS-DE-TRAD-SUBORDONNANTE","TRAD-ALL-FR-SUBORDONNANTE",
                        "TRAD-FR-SUBORDONNANTE","TRAD-ALL-SUBORDONNANTE");

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
                $uniteTrads = $traitementLing->{'UNITE-TRAD'};
                $data['meanings'] = [];
                //$i = 0;
                if ($uniteTrads) {
                    foreach ($uniteTrads as $uniteTrad) {
                        $key_1 =  array_keys(get_object_vars($uniteTrad))[0];
                        // Just to be sure we will not be surprised!
                        if (in_array($key_1, $unite_trads_col)){
                            $pasDeTradSubordonnante = $uniteTrad->{$key_1}[0];
                            if ($pasDeTradSubordonnante) {
                                $unitesDeSens = $pasDeTradSubordonnante->{'UNITE-DE-SENS'};
                                if ($unitesDeSens) {
                                    foreach ($unitesDeSens as $uniteDeSens) {
                                        $domSpec      = $uniteDeSens->{'DOM-SPEC'};
                                        $marqueUsage  = $uniteDeSens->{'MARQUE-USAGE'};
                                        $meaningLux   = $uniteDeSens->{'UNITE-POLYLEX-LUX'};
                                        $equivTradAll = $uniteDeSens->{'EQUIV-TRAD-ALL'};
                                        $equivTradFr  = $uniteDeSens->{'EQUIV-TRAD-FR'};
                                        $equivTradEn  = $uniteDeSens->{'EQUIV-TRAD-EN'};
                                        $equivTradPo  = $uniteDeSens->{'EQUIV-TRAD-PO'};
                                        $examples     = $uniteDeSens->{'EXEMPLIFICATION'};
                                        $synonyms     = $uniteDeSens->{'SYNONYMES'}; 

                                        $meaning['translations'] = [];
                                        $meaning['translations'] = [
                                            'lb' => [],
                                            'de' => [],
                                            'fr' => [],
                                            'en' => [],
                                            'pt' => [],
                                        ];
                                        $meaning['examples'] = [];
                                        $meaning['translations']['lb'][] = (string)$meaningLux;

                                        foreach ($equivTradAll->children() as $translation) {
                                            if (!strpos($translation->getName(), 'ABSENTE')) {
                                                if (strpos($translation->getName(), 'ETA-PRESENTE')){
                                                    $meaning['translations']['de'][] = "[".(string)$translation."]";
                                                } else {
                                                    $meaning['translations']['de'][] = (string)$translation;
                                                }
                                            }
                                        }

                                        foreach ($equivTradFr->children() as $translation) {
                                            if (!strpos($translation->getName(), 'ABSENTE')) {
                                                if (strpos($translation->getName(), 'ETF-PRESENTE')){
                                                    $meaning['translations']['fr'][] = "[".(string)$translation."]";
                                                } else {
                                                    $meaning['translations']['fr'][] = (string)$translation;
                                                }
                                            }
                                        }

                                        if ($equivTradEn) {
                                            foreach ($equivTradEn->children() as $translation) {
                                                if (!strpos($translation->getName(), 'ABSENTE')) {
                                                    if (strpos($translation->getName(), 'ETE-PRESENTE')){
                                                        $meaning['translations']['en'][] = "[".(string)$translation."]";
                                                    } else {
                                                        $meaning['translations']['en'][] = (string)$translation;
                                                    }
                                                }
                                            }
                                        }

                                        if ($equivTradPo) {
                                            foreach ($equivTradPo->children() as $translation) {
                                                if (!strpos($translation->getName(), 'ABSENTE')) {
                                                    if (strpos($translation->getName(), 'ETP-PRESENTE')){
                                                        $meaning['translations']['pt'][] = "[".(string)$translation."]";
                                                    } else {
                                                        $meaning['translations']['pt'][] = (string)$translation;
                                                    }
                                                }
                                            }
                                        }

                                        foreach ($examples->children() as $example) {
                                            $example = $example->{'TEXTE-EX'};
                                            $example_text = "";
                                            foreach ($example->children() as $chunk) {
                                                if ($chunk->getName()=='TEXTE') {
                                                    if (preg_match('/[\?!.;]$/',(string)$chunk)){
                                                        $example_text = substr($example_text, 0, -1).(string)$chunk;
                                                    } else {
                                                        $example_text .= (string)$chunk;
                                                    }
                                                }
                                                if ($chunk->getName()=='ABREV-AD') {
                                                    $sign = (string)$chunk;
                                                    if (preg_match('/\.e$/',$sign)){
                                                        $example_text .= " ".$data['lemma']."e ";
                                                    } elseif (preg_match('/[a-zA-Z\x7f-\xff]\.$/', $sign)) {
                                                        if (mb_substr($example_text,-1) === '\''){
                                                            $example_text .= $data['lemma']." ";    
                                                        } else {
                                                            $example_text .= " ".$data['lemma']." ";
                                                        }
                                                    } else {
                                                        if (mb_substr($example_text,-1) === '\''){
                                                            $example_text .= $sign." ";    
                                                        } else {
                                                            $example_text .= " ".$sign." ";
                                                        }                                                
                                                    }
                                                }
                                            }
                                            $meaning['examples'][] = trim((string)$example_text);
                                        }
                                        $data['meanings'][] = $meaning;
                                    }     
                                }
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

    if ($debug){
        break;
    }
}

file_put_contents('lod.json', json_encode($lemmas, JSON_PRETTY_PRINT));
